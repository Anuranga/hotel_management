<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HotelQrOrderBillingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_tamper_proof_qr_route_rejects_unsigned_requests_and_allows_signed_url(): void
    {
        $table = RestaurantTable::firstOrFail();

        $this->get("/table/{$table->qr_token}")
            ->assertForbidden();

        $signedUrl = URL::signedRoute('table.entry', ['qr_token' => $table->qr_token]);

        $this->get($signedUrl)
            ->assertOk()
            ->assertSee($table->table_number);
    }

    public function test_guest_order_submission_validates_phone_sanitizes_input_locks_stock_and_appends_batches(): void
    {
        $table = RestaurantTable::where('status', 'vacant')->firstOrFail();
        $taxableItem = MenuItem::where('is_vat_exempt', false)->where('current_stock', '>', 5)->firstOrFail();
        $exemptItem = MenuItem::where('is_vat_exempt', true)->firstOrFail();

        $initialStock = $taxableItem->current_stock;

        // Invalid Sri Lankan phone number should fail validation
        $this->postJson(route('order.place'), [
            'restaurant_table_id' => $table->id,
            'customer_name' => '<b>Kasun Perera</b>',
            'customer_phone' => '0112345678',
            'items' => [
                ['menu_item_id' => $taxableItem->id, 'quantity' => 2],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_phone']);

        // Valid order placement
        $response = $this->postJson(route('order.place'), [
            'restaurant_table_id' => $table->id,
            'customer_name' => '<script>alert(1)</script>Kasun Perera',
            'customer_phone' => '+94771234567',
            'items' => [
                [
                    'menu_item_id' => $taxableItem->id,
                    'quantity' => 2,
                    'notes' => '<b>Less spicy please</b>',
                ],
            ],
        ]);

        $response->assertCreated();

        $order = Order::firstOrFail();
        $this->assertSame('alert(1)Kasun Perera', $order->customer_name);
        $this->assertSame('pending_confirmation', $order->order_status);
        $this->assertSame('occupied', $table->fresh()?->status);
        $this->assertSame($initialStock - 2, $taxableItem->fresh()?->current_stock);

        $this->assertDatabaseHas('inventory_logs', [
            'menu_item_id' => $taxableItem->id,
            'order_id' => $order->id,
            'quantity_change' => -2,
            'reason' => 'sale',
        ]);

        // Append Batch #2 using session token header
        $appendResponse = $this->withHeaders([
            'X-Table-Session-Token' => $order->session_token,
        ])->postJson(route('order.append', ['order' => $order->id]), [
            'items' => [
                [
                    'menu_item_id' => $exemptItem->id,
                    'quantity' => 1,
                    'notes' => 'Chilled',
                ],
            ],
        ]);

        $appendResponse->assertOk();
        $this->assertSame(2, $order->batches()->count());

        $expectedSubtotal = round(($taxableItem->price * 2) + $exemptItem->price, 2);
        $this->assertEquals($expectedSubtotal, (float) $order->fresh()?->gross_subtotal);
    }

    public function test_staff_confirmation_kitchen_kds_preparation_and_batch_delivery_workflow(): void
    {
        $table = RestaurantTable::firstOrFail();
        $menuItem = MenuItem::where('is_vat_exempt', false)->firstOrFail();

        $this->postJson(route('order.place'), [
            'restaurant_table_id' => $table->id,
            'customer_name' => 'Amaya Senanayake',
            'customer_phone' => '0719876543',
            'items' => [
                ['menu_item_id' => $menuItem->id, 'quantity' => 1, 'notes' => 'No onions'],
            ],
        ])->assertCreated();

        $order = Order::with('batches.items')->firstOrFail();
        $batch = $order->batches->firstOrFail();

        $staffUser = User::whereHas('role', fn ($q) => $q->where('name', Role::STAFF))->firstOrFail();
        $kitchenUser = User::whereHas('role', fn ($q) => $q->where('name', Role::KITCHEN))->firstOrFail();

        // Kitchen cannot access Cashier Billing
        $this->actingAs($kitchenUser)
            ->get(route('cashier.billing'))
            ->assertForbidden();

        // 1. Staff confirms pending order
        $this->actingAs($staffUser)
            ->postJson(route('staff.orders.confirm', ['order' => $order->id]))
            ->assertOk();

        $this->assertSame('confirmed', $order->fresh()?->order_status);
        $this->assertSame('confirmed', $batch->fresh()?->status);

        // 2. Kitchen KDS polls active cards (strictly no financial figures) and transitions Cooking -> Ready
        $kdsJson = $this->actingAs($kitchenUser)
            ->getJson(route('kitchen.display', ['poll' => 1]))
            ->assertOk()
            ->json();

        $this->assertNotEmpty($kdsJson['cards']);
        $this->assertArrayNotHasKey('unit_price', $kdsJson['cards'][0]['items'][0]);
        $this->assertArrayNotHasKey('grand_total', $kdsJson['cards'][0]);

        $this->actingAs($kitchenUser)
            ->postJson(route('kitchen.batches.status', ['batch' => $batch->id]), ['status' => 'preparing'])
            ->assertOk();

        $this->assertSame('preparing', $order->fresh()?->order_status);

        $this->actingAs($kitchenUser)
            ->postJson(route('kitchen.batches.status', ['batch' => $batch->id]), ['status' => 'ready'])
            ->assertOk();

        $this->assertSame('ready', $batch->fresh()?->status);

        // 3. Staff picks up ready batch and marks delivered -> Order becomes served
        $this->actingAs($staffUser)
            ->postJson(route('staff.batches.deliver', ['batch' => $batch->id]))
            ->assertOk();

        $this->assertSame('delivered', $batch->fresh()?->status);
        $this->assertSame('served', $order->fresh()?->order_status);
    }

    public function test_cashier_discount_override_pin_gate_strict_billing_settlement_and_audit_trail(): void
    {
        $table = RestaurantTable::firstOrFail();
        $category = Category::firstOrFail();

        $item = MenuItem::create([
            'category_id' => $category->id,
            'name' => 'Test Banquet Platter',
            'price' => 1000.00,
            'cost_price' => 400.00,
            'is_available' => true,
            'is_vat_exempt' => false,
            'current_stock' => 10,
        ]);

        $this->postJson(route('order.place'), [
            'restaurant_table_id' => $table->id,
            'customer_name' => 'Nimal Rajapakse',
            'customer_phone' => '0772223344',
            'items' => [
                ['menu_item_id' => $item->id, 'quantity' => 2],
            ],
        ])->assertCreated();

        $order = Order::firstOrFail();
        $cashier = User::whereHas('role', fn ($q) => $q->where('name', Role::CASHIER))->firstOrFail();
        $manager = User::whereHas('role', fn ($q) => $q->where('name', Role::MANAGER))->firstOrFail();
        $admin = User::whereHas('role', fn ($q) => $q->where('name', Role::ADMIN))->firstOrFail();

        // Non-admin phone masking check (077****344)
        $this->assertSame('077****344', $order->displayPhoneFor($cashier));
        $this->assertSame('077****344', $order->displayPhoneFor($manager));
        $this->assertSame('0772223344', $order->displayPhoneFor($admin));

        // 1. Discount > 10% without Manager PIN must fail validation
        $this->actingAs($cashier)
            ->postJson(route('cashier.orders.discount', ['order' => $order->id]), [
                'discount_percentage' => 20,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['manager_pin']);

        // 2. Discount > 10% with wrong Manager PIN must fail validation
        $this->actingAs($cashier)
            ->postJson(route('cashier.orders.discount', ['order' => $order->id]), [
                'discount_percentage' => 20,
                'manager_pin' => '0000',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['manager_pin']);

        // 3. Discount 20% with valid Manager PIN ('1234') succeeds and logs discount_override in audit_logs
        $this->actingAs($cashier)
            ->postJson(route('cashier.orders.discount', ['order' => $order->id]), [
                'discount_percentage' => 20,
                'manager_pin' => '1234',
            ])
            ->assertOk();

        $order->refresh();

        // Strict calculation verification:
        // Gross Subtotal = 2000.00
        // Discount (20%) = 400.00
        // Net Taxable = 1600.00
        // VAT (18%) = round(1600 * 0.18, 2) = 288.00
        // Service Charge (10%) = round(1600 * 0.10, 2) = 160.00
        // Grand Total = 1600 + 288 + 160 = 2048.00
        $this->assertEquals(2000.00, (float) $order->gross_subtotal);
        $this->assertEquals(400.00, (float) $order->discount_amount);
        $this->assertEquals(288.00, (float) $order->vat_amount);
        $this->assertEquals(160.00, (float) $order->service_charge_amount);
        $this->assertEquals(2048.00, (float) $order->grand_total);
        $this->assertSame($manager->id, $order->discount_approved_by);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'discount_override',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
        ]);

        // 4. Settle order and verify table resets to vacant and SMS receipt is dispatched
        $settleRes = $this->actingAs($cashier)
            ->postJson(route('cashier.orders.settle', ['order' => $order->id]), [
                'payment_method' => 'card',
            ])
            ->assertOk()
            ->json();

        $order->refresh();
        $this->assertSame('settled', $order->order_status);
        $this->assertSame('card', $order->payment_method);
        $this->assertNotNull($order->settled_at);
        $this->assertSame('vacant', $table->fresh()?->status);
        $this->assertTrue($settleRes['sms_receipt']['sent']);
        $this->assertStringContainsString('signature=', $settleRes['sms_receipt']['payload']['receipt_url']);

        // 5. Admin price update triggers price_update audit log
        $this->actingAs($admin)
            ->putJson(route('admin.menu-items.update', ['menuItem' => $item->id]), [
                'category_id' => $item->category_id,
                'name' => $item->name,
                'description' => $item->description,
                'price' => 1250.00,
                'cost_price' => 450.00,
                'is_available' => true,
                'is_vat_exempt' => false,
                'current_stock' => 10,
            ])
            ->assertOk();

        $this->assertTrue(
            AuditLog::where('event_type', 'price_update')
                ->where('auditable_id', $item->id)
                ->exists()
        );
    }
}
