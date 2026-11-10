<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppendOrderItemsRequest;
use App\Http\Requests\PlaceOrderRequest;
use App\Models\Category;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use App\Models\RestaurantTable;
use App\Services\OrderBillingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StaffOrderController extends Controller
{
    public function __construct(
        public OrderBillingService $billingService
    ) {}

    /**
     * Render the handheld sales assistant dashboard or return polling JSON (every 3 seconds).
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->expectsJson() || $request->boolean('poll')) {
            return response()->json($this->buildDashboardPayload($request));
        }

        $categories = Category::query()
            ->active()
            ->with(['menuItems' => function ($query): void {
                $query->where('is_available', true)->orderBy('name');
            }])
            ->get();

        $tables = RestaurantTable::query()
            ->with('activeOrder')
            ->orderBy('table_number')
            ->get();

        return view('staff.orders', [
            'initialPayload' => $this->buildDashboardPayload($request),
            'categories' => $categories,
            'tables' => $tables,
        ]);
    }

    /**
     * Allow a Sales Assistant to create a new table order directly for customers without a QR smartphone.
     */
    public function createWalkInOrder(PlaceOrderRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        /** @var Order $order */
        $order = DB::transaction(function () use ($validated): Order {
            $table = RestaurantTable::query()
                ->whereKey($validated['restaurant_table_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $order = Order::create([
                'restaurant_table_id' => $table->id,
                'session_token' => (string) Str::uuid(),
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'order_status' => 'confirmed',
                'vat_rate' => (float) config('hotel.vat_rate', 18.00),
                'service_charge_rate' => (float) config('hotel.service_charge_rate', 10.00),
                'payment_method' => 'unpaid',
            ]);

            $batch = OrderBatch::create([
                'order_id' => $order->id,
                'batch_number' => 1,
                'status' => 'confirmed',
            ]);

            $this->billingService->attachItemsToBatchWithLock($order, $batch, $validated['items']);

            $table->update(['status' => 'occupied']);

            return $this->billingService->recalculateOrder($order);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Walk-in Order #{$order->id} created and routed to Kitchen & Bar.",
                'payload' => $this->buildDashboardPayload($request),
            ], 201);
        }

        return redirect()->back()->with('status', "Assisted Order #{$order->id} created for Table {$order->restaurantTable?->table_number} and sent to Kitchen/Bar.");
    }

    /**
     * One-tap "Acknowledge & Confirm" transition:
     * Transitions order from pending_confirmation to confirmed, confirms pending batches,
     * and dispatches items to Kitchen / Bar queues.
     */
    public function confirmOrder(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedOrder->batches()
                ->where('status', 'pending')
                ->update(['status' => 'confirmed']);

            if ($lockedOrder->order_status === 'pending_confirmation') {
                $lockedOrder->update(['order_status' => 'confirmed']);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Order #{$order->id} confirmed and dispatched to Kitchen & Bar queues.",
                'payload' => $this->buildDashboardPayload($request),
            ]);
        }

        return redirect()->back()->with('status', "Order #{$order->id} confirmed and routed to stations.");
    }

    /**
     * Mark an OrderBatch as delivered by staff and advance master Order to 'served' when all batches are delivered.
     */
    public function deliverBatch(Request $request, OrderBatch $batch): JsonResponse|RedirectResponse
    {
        DB::transaction(function () use ($batch): void {
            $lockedBatch = OrderBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedOrder = Order::query()
                ->whereKey($lockedBatch->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedBatch->update(['status' => 'delivered']);

            $lockedBatch->items()
                ->where('item_status', '!=', 'cancelled')
                ->update(['item_status' => 'served']);

            $hasUndeliveredBatches = $lockedOrder->batches()
                ->where('status', '!=', 'delivered')
                ->exists();

            if (! $hasUndeliveredBatches && $lockedOrder->isActive()) {
                $lockedOrder->update(['order_status' => 'served']);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Batch #{$batch->batch_number} marked as delivered.",
                'payload' => $this->buildDashboardPayload($request),
            ]);
        }

        return redirect()->back()->with('status', "Batch #{$batch->batch_number} marked as delivered.");
    }

    /**
     * Final Sales Assistant Confirmation: Confirm all order items are completed and send the bill to Cashier.
     */
    public function completeAndSendToCashier(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedOrder->batches()
                ->where('status', '!=', 'delivered')
                ->update(['status' => 'delivered']);

            $lockedOrder->items()
                ->where('item_status', '!=', 'cancelled')
                ->update(['item_status' => 'served']);

            $lockedOrder->update([
                'order_status' => 'served',
                'bill_requested_at' => now(),
            ]);

            $this->billingService->recalculateOrder($lockedOrder);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Order #{$order->id} confirmed complete and sent to Cashier for billing!",
                'payload' => $this->buildDashboardPayload($request),
            ]);
        }

        return redirect()->back()->with('status', "Order #{$order->id} marked completed and dispatched to Cashier Billing!");
    }

    /**
     * Allow staff to verbally take and append add-on items directly to an active table's order.
     */
    public function appendStaffItems(AppendOrderItemsRequest $request, Order $order): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($order, $validated): void {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $nextBatchNumber = ((int) $lockedOrder->batches()->max('batch_number')) + 1;

            $batch = OrderBatch::create([
                'order_id' => $lockedOrder->id,
                'batch_number' => $nextBatchNumber,
                'status' => 'confirmed',
            ]);

            $this->billingService->attachItemsToBatchWithLock($lockedOrder, $batch, $validated['items']);

            if ($lockedOrder->order_status === 'served') {
                $lockedOrder->order_status = 'confirmed';
                $lockedOrder->bill_requested_at = null;
                $lockedOrder->save();
            }

            $this->billingService->recalculateOrder($lockedOrder);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Add-on batch appended to Order #{$order->id}.",
                'payload' => $this->buildDashboardPayload($request),
            ]);
        }

        return redirect()->back()->with('status', "Add-on items appended to Order #{$order->id}.");
    }

    /**
     * Cancel an individual OrderItem, restore inventory if finite, log to audit_logs, and recalculate order.
     */
    public function cancelItem(Request $request, OrderItem $item): JsonResponse|RedirectResponse
    {
        DB::transaction(function () use ($request, $item): void {
            $lockedItem = OrderItem::query()
                ->with(['batch.order', 'menuItem'])
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedItem->item_status === 'cancelled') {
                return;
            }

            $oldStatus = $lockedItem->item_status;
            $lockedItem->update(['item_status' => 'cancelled']);

            if ($lockedItem->menuItem !== null && $lockedItem->menuItem->current_stock !== -1) {
                $lockedItem->menuItem->increment('current_stock', $lockedItem->quantity);

                InventoryLog::create([
                    'menu_item_id' => $lockedItem->menuItem->id,
                    'order_id' => $lockedItem->batch?->order_id,
                    'quantity_change' => $lockedItem->quantity,
                    'reason' => 'restock',
                ]);
            }

            $this->billingService->recordAudit(
                'item_cancellation',
                $lockedItem,
                ['item_status' => $oldStatus, 'quantity' => $lockedItem->quantity],
                ['item_status' => 'cancelled'],
                $request->user()?->id,
                (string) $request->ip()
            );

            if ($lockedItem->batch?->order !== null) {
                $this->billingService->recalculateOrder($lockedItem->batch->order);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Order item cancelled and bill recalculated.',
                'payload' => $this->buildDashboardPayload($request),
            ]);
        }

        return redirect()->back()->with('status', 'Order item cancelled.');
    }

    /**
     * Build the structured payload for the handheld sales assistant view.
     *
     * @return array<string, mixed>
     */
    private function buildDashboardPayload(Request $request): array
    {
        $viewer = $request->user();

        $activeOrders = Order::query()
            ->active()
            ->with([
                'restaurantTable',
                'batches.items.menuItem.category',
            ])
            ->orderByRaw("CASE WHEN order_status = 'pending_confirmation' THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->get();

        $pendingCount = $activeOrders->where('order_status', 'pending_confirmation')->count();

        $ordersFormatted = $activeOrders->map(function (Order $order) use ($viewer): array {
            $hasPendingBatch = $order->batches->contains(fn (OrderBatch $b): bool => $b->status === 'pending');

            return [
                'id' => $order->id,
                'table_id' => $order->restaurant_table_id,
                'table_number' => $order->restaurantTable?->table_number ?? 'N/A',
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->displayPhoneFor($viewer),
                'order_status' => $order->order_status,
                'needs_confirmation' => $order->order_status === 'pending_confirmation' || $hasPendingBatch,
                'is_sent_to_cashier' => $order->bill_requested_at !== null || $order->order_status === 'served',
                'bill_requested_human' => $order->bill_requested_at?->format('h:i A'),
                'gross_subtotal' => number_format((float) $order->gross_subtotal, 2, '.', ''),
                'grand_total' => number_format((float) $order->grand_total, 2, '.', ''),
                'created_at_human' => $order->created_at?->diffForHumans(),
                'batches' => $order->batches->map(function (OrderBatch $batch): array {
                    $kitchenItems = [];
                    $barItems = [];

                    foreach ($batch->items as $item) {
                        $station = $item->menuItem?->category?->station ?? 'kitchen';
                        $entry = [
                            'id' => $item->id,
                            'name' => $item->menuItem?->name ?? 'Menu Item',
                            'quantity' => $item->quantity,
                            'notes' => $item->notes,
                            'item_status' => $item->item_status,
                            'station' => $station,
                        ];

                        if ($station === 'bar') {
                            $barItems[] = $entry;
                        } else {
                            $kitchenItems[] = $entry;
                        }
                    }

                    return [
                        'id' => $batch->id,
                        'batch_number' => $batch->batch_number,
                        'status' => $batch->status,
                        'kitchen_items' => $kitchenItems,
                        'bar_items' => $barItems,
                        'all_items' => array_merge($kitchenItems, $barItems),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return [
            'pending_confirmation_count' => $pendingCount,
            'active_orders_count' => $activeOrders->count(),
            'orders' => $ordersFormatted,
            'polled_at' => now()->format('h:i:s A'),
        ];
    }
}
