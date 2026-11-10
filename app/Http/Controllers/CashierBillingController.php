<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppendOrderItemsRequest;
use App\Http\Requests\ApplyDiscountRequest;
use App\Http\Requests\SettleOrderRequest;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\RestaurantTable;
use App\Services\OrderBillingService;
use App\Services\SmsReceiptService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashierBillingController extends Controller
{
    public function __construct(
        public OrderBillingService $billingService,
        public SmsReceiptService $smsReceiptService
    ) {}

    /**
     * Render the Cashier Settlement & Billing Terminal.
     */
    public function index(Request $request): View
    {
        $viewer = $request->user();

        $activeOrders = Order::query()
            ->active()
            ->with([
                'restaurantTable',
                'batches.items.menuItem.category',
                'discountApprover',
            ])
            ->orderByRaw("CASE WHEN bill_requested_at IS NOT NULL OR order_status = 'served' THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->get();

        $recentSettledOrders = Order::query()
            ->settled()
            ->with([
                'restaurantTable',
                'batches.items.menuItem.category',
                'discountApprover',
            ])
            ->latest('settled_at')
            ->limit(10)
            ->get();

        $selectedOrderId = $request->integer('order_id') ?: ($activeOrders->first()?->id ?? $recentSettledOrders->first()?->id);
        $selectedOrder = null;

        if ($selectedOrderId) {
            $selectedOrder = $activeOrders->firstWhere('id', $selectedOrderId)
                ?? $recentSettledOrders->firstWhere('id', $selectedOrderId)
                ?? Order::with(['restaurantTable', 'batches.items.menuItem.category', 'discountApprover'])->find($selectedOrderId);
        }

        $categories = Category::query()
            ->active()
            ->with(['menuItems' => function ($query): void {
                $query->where('is_available', true)->orderBy('name');
            }])
            ->get();

        return view('cashier.terminal', [
            'activeOrders' => $activeOrders,
            'recentSettledOrders' => $recentSettledOrders,
            'selectedOrder' => $selectedOrder,
            'categories' => $categories,
            'viewer' => $viewer,
            'maxStandardDiscount' => (float) config('hotel.max_cashier_discount_percentage', 10.00),
            'hotelName' => (string) config('hotel.name', 'Odara Food Pvt Limited'),
        ]);
    }

    /**
     * Apply discount, service charge rate, tax/VAT rate, and extra charges to an active order.
     * Discounts > 10% require Manager/Admin PIN verification and trigger an audit_logs entry.
     */
    public function applyDiscount(ApplyDiscountRequest $request, Order $order): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $discountPercentage = round((float) $validated['discount_percentage'], 2);
        $maxStandard = (float) config('hotel.max_cashier_discount_percentage', 10.00);

        $updatedOrder = DB::transaction(function () use ($request, $order, $discountPercentage, $maxStandard, $validated): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedOrder->isActive()) {
                throw ValidationException::withMessages([
                    'order' => ['Cannot modify charges or discounts on a settled or cancelled order.'],
                ]);
            }

            $oldDiscountPct = (float) $lockedOrder->discount_percentage;
            $oldDiscountAmt = (float) $lockedOrder->discount_amount;
            $approverId = null;

            if ($discountPercentage > $maxStandard) {
                $authorizer = $this->billingService->verifyManagerOrAdminPin((string) ($validated['manager_pin'] ?? ''));

                if ($authorizer === null) {
                    throw ValidationException::withMessages([
                        'manager_pin' => ['Invalid Manager or Admin PIN code for discount override.'],
                    ]);
                }

                $approverId = $authorizer->id;
            }

            $vatRate = array_key_exists('vat_rate', $validated) && $validated['vat_rate'] !== null
                ? round((float) $validated['vat_rate'], 2)
                : (float) $lockedOrder->vat_rate;

            $serviceChargeRate = array_key_exists('service_charge_rate', $validated) && $validated['service_charge_rate'] !== null
                ? round((float) $validated['service_charge_rate'], 2)
                : (float) $lockedOrder->service_charge_rate;

            $extraChargeAmount = array_key_exists('extra_charge_amount', $validated) && $validated['extra_charge_amount'] !== null
                ? round((float) $validated['extra_charge_amount'], 2)
                : (float) ($lockedOrder->extra_charge_amount ?? 0.0);

            $extraChargeLabel = array_key_exists('extra_charge_label', $validated)
                ? (string) $validated['extra_charge_label']
                : $lockedOrder->extra_charge_label;

            $lockedOrder->discount_approved_by = $approverId;
            $recalculated = $this->billingService->recalculateOrder(
                $lockedOrder,
                $discountPercentage,
                $vatRate,
                $serviceChargeRate,
                $extraChargeAmount,
                $extraChargeLabel
            );

            if ($discountPercentage > $maxStandard) {
                $this->billingService->recordAudit(
                    'discount_override',
                    $recalculated,
                    [
                        'discount_percentage' => $oldDiscountPct,
                        'discount_amount' => $oldDiscountAmt,
                    ],
                    [
                        'discount_percentage' => (float) $recalculated->discount_percentage,
                        'discount_amount' => (float) $recalculated->discount_amount,
                        'approved_by_user_id' => $approverId,
                    ],
                    $approverId ?? $request->user()?->id,
                    (string) $request->ip()
                );
            }

            return $recalculated;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Bill charges, taxes, and discount ({$updatedOrder->discount_percentage}%) updated.",
                'order' => $updatedOrder->fresh(['restaurantTable', 'discountApprover']),
            ]);
        }

        return redirect()
            ->route('cashier.billing', ['order_id' => $updatedOrder->id])
            ->with('status', "Bill charges, taxes, and discount updated for Order #{$updatedOrder->id}.");
    }

    /**
     * Allow Cashier to append additional menu items directly at the billing counter before settlement.
     */
    public function appendCashierItems(AppendOrderItemsRequest $request, Order $order): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($order, $validated): void {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedOrder->isActive()) {
                throw ValidationException::withMessages([
                    'order' => ['Cannot append items to a settled or cancelled order.'],
                ]);
            }

            $nextBatchNumber = ((int) $lockedOrder->batches()->max('batch_number')) + 1;

            $batch = OrderBatch::create([
                'order_id' => $lockedOrder->id,
                'batch_number' => $nextBatchNumber,
                'status' => 'delivered',
            ]);

            $createdItems = $this->billingService->attachItemsToBatchWithLock($lockedOrder, $batch, $validated['items']);

            foreach ($createdItems as $item) {
                $item->update(['item_status' => 'served']);
            }

            $this->billingService->recalculateOrder($lockedOrder);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Items added to Order #{$order->id} bill.",
            ]);
        }

        return redirect()
            ->route('cashier.billing', ['order_id' => $order->id])
            ->with('status', "Additional items appended to Order #{$order->id} invoice.");
    }

    /**
     * Settle and checkout an active order inside a pessimistic DB transaction,
     * reset the table to vacant, and dispatch the rate-limited SMS receipt.
     */
    public function settle(SettleOrderRequest $request, Order $order): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $maxStandard = (float) config('hotel.max_cashier_discount_percentage', 10.00);

        /** @var Order $settledOrder */
        $settledOrder = DB::transaction(function () use ($request, $order, $validated, $maxStandard): Order {
            $lockedOrder = Order::query()
                ->with(['batches.items.menuItem'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->order_status === 'settled') {
                throw ValidationException::withMessages([
                    'order' => ["Order #{$lockedOrder->id} has already been settled."],
                ]);
            }

            if ($lockedOrder->order_status === 'cancelled') {
                throw ValidationException::withMessages([
                    'order' => ["Cannot settle cancelled Order #{$lockedOrder->id}."],
                ]);
            }

            $discountPercentage = array_key_exists('discount_percentage', $validated) && $validated['discount_percentage'] !== null
                ? round((float) $validated['discount_percentage'], 2)
                : (float) $lockedOrder->discount_percentage;

            $vatRate = array_key_exists('vat_rate', $validated) && $validated['vat_rate'] !== null
                ? round((float) $validated['vat_rate'], 2)
                : (float) $lockedOrder->vat_rate;

            $serviceChargeRate = array_key_exists('service_charge_rate', $validated) && $validated['service_charge_rate'] !== null
                ? round((float) $validated['service_charge_rate'], 2)
                : (float) $lockedOrder->service_charge_rate;

            $extraChargeAmount = array_key_exists('extra_charge_amount', $validated) && $validated['extra_charge_amount'] !== null
                ? round((float) $validated['extra_charge_amount'], 2)
                : (float) ($lockedOrder->extra_charge_amount ?? 0.0);

            $extraChargeLabel = array_key_exists('extra_charge_label', $validated)
                ? (string) $validated['extra_charge_label']
                : $lockedOrder->extra_charge_label;

            $oldDiscountPct = (float) $lockedOrder->discount_percentage;
            $oldDiscountAmt = (float) $lockedOrder->discount_amount;

            if ($discountPercentage > $maxStandard) {
                $pinProvided = (string) ($validated['manager_pin'] ?? '');

                if ($pinProvided !== '') {
                    $authorizer = $this->billingService->verifyManagerOrAdminPin($pinProvided);

                    if ($authorizer === null) {
                        throw ValidationException::withMessages([
                            'manager_pin' => ['Invalid Manager or Admin PIN code for discount override.'],
                        ]);
                    }

                    $lockedOrder->discount_approved_by = $authorizer->id;
                } elseif ($lockedOrder->discount_approved_by === null || abs($discountPercentage - $oldDiscountPct) > 0.005) {
                    throw ValidationException::withMessages([
                        'manager_pin' => ["Discounts exceeding {$maxStandard}% require a valid Manager or Admin PIN code."],
                    ]);
                }
            } elseif ($discountPercentage <= $maxStandard) {
                $lockedOrder->discount_approved_by = null;
            }

            $recalculated = $this->billingService->recalculateOrder(
                $lockedOrder,
                $discountPercentage,
                $vatRate,
                $serviceChargeRate,
                $extraChargeAmount,
                $extraChargeLabel
            );

            if ($discountPercentage > $maxStandard && abs($discountPercentage - $oldDiscountPct) > 0.005) {
                $this->billingService->recordAudit(
                    'discount_override',
                    $recalculated,
                    [
                        'discount_percentage' => $oldDiscountPct,
                        'discount_amount' => $oldDiscountAmt,
                    ],
                    [
                        'discount_percentage' => (float) $recalculated->discount_percentage,
                        'discount_amount' => (float) $recalculated->discount_amount,
                        'approved_by_user_id' => $recalculated->discount_approved_by,
                    ],
                    $recalculated->discount_approved_by ?? $request->user()?->id,
                    (string) $request->ip()
                );
            }

            $recalculated->update([
                'order_status' => 'settled',
                'payment_method' => $validated['payment_method'],
                'settled_at' => now(),
            ]);

            $recalculated->batches()
                ->where('status', '!=', 'delivered')
                ->update(['status' => 'delivered']);

            $recalculated->items()
                ->where('item_status', '!=', 'cancelled')
                ->update(['item_status' => 'served']);

            $lockedTable = RestaurantTable::query()
                ->whereKey($recalculated->restaurant_table_id)
                ->lockForUpdate()
                ->first();

            if ($lockedTable !== null) {
                $hasOtherActiveOrders = Order::query()
                    ->where('restaurant_table_id', $lockedTable->id)
                    ->where('id', '!=', $recalculated->id)
                    ->active()
                    ->exists();

                if (! $hasOtherActiveOrders) {
                    $lockedTable->update(['status' => 'vacant']);
                }
            }

            return $recalculated->fresh(['restaurantTable', 'batches.items.menuItem', 'discountApprover']);
        });

        $smsResult = $this->smsReceiptService->sendReceipt($settledOrder);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Order #{$settledOrder->id} settled via ".strtoupper($settledOrder->payment_method).'.',
                'order' => $settledOrder,
                'sms_receipt' => $smsResult,
            ]);
        }

        $smsBanner = $smsResult['sent']
            ? 'Digital SMS receipt dispatched to customer.'
            : 'SMS receipt rate-limited; receipt ready for thermal print.';

        return redirect()
            ->route('cashier.billing', ['order_id' => $settledOrder->id, 'print' => 1])
            ->with('status', "Order #{$settledOrder->id} settled successfully! {$smsBanner}");
    }
}
