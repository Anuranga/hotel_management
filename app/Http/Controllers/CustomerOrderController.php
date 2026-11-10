<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppendOrderItemsRequest;
use App\Http\Requests\PlaceOrderRequest;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\RestaurantTable;
use App\Services\OrderBillingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerOrderController extends Controller
{
    public function __construct(
        public OrderBillingService $billingService
    ) {}

    /**
     * Resolve table QR token, verify signed URL, and either resume an active order or render the guest menu.
     */
    public function entry(Request $request, string $qr_token): View|RedirectResponse
    {
        if (! $this->hasValidProxyAwareSignature($request)) {
            abort(403, 'Invalid or tampered QR code signature.');
        }

        $table = RestaurantTable::query()
            ->where('qr_token', $qr_token)
            ->with('activeOrder')
            ->firstOrFail();

        $cookieName = (string) config('hotel.table_session_cookie', 'hotel_table_session_token');
        $clientToken = $request->cookie($cookieName) ?? $request->session()->get($cookieName);

        if (
            $table->activeOrder !== null &&
            is_string($clientToken) &&
            hash_equals((string) $table->activeOrder->session_token, $clientToken)
        ) {
            return redirect()->route('order.live-status', ['order' => $table->activeOrder->id]);
        }

        $categories = Category::query()
            ->active()
            ->with(['menuItems' => function ($query): void {
                $query->orderBy('name');
            }])
            ->get();

        return view('guest.menu', [
            'table' => $table,
            'categories' => $categories,
            'vatRate' => (float) config('hotel.vat_rate', 18.00),
            'serviceChargeRate' => (float) config('hotel.service_charge_rate', 10.00),
        ]);
    }

    /**
     * Place the initial order for a table inside a database transaction with pessimistic row locking.
     */
    public function placeOrder(PlaceOrderRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        /** @var Order $order */
        $order = DB::transaction(function () use ($validated): Order {
            $table = RestaurantTable::query()
                ->whereKey($validated['restaurant_table_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $sessionToken = (string) Str::uuid();

            $order = Order::create([
                'restaurant_table_id' => $table->id,
                'session_token' => $sessionToken,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'order_status' => 'pending_confirmation',
                'vat_rate' => (float) config('hotel.vat_rate', 18.00),
                'service_charge_rate' => (float) config('hotel.service_charge_rate', 10.00),
                'payment_method' => 'unpaid',
            ]);

            $batch = OrderBatch::create([
                'order_id' => $order->id,
                'batch_number' => 1,
                'status' => 'pending',
            ]);

            $this->billingService->attachItemsToBatchWithLock($order, $batch, $validated['items']);

            $table->update(['status' => 'occupied']);

            return $this->billingService->recalculateOrder($order);
        });

        $cookieName = (string) config('hotel.table_session_cookie', 'hotel_table_session_token');
        $ttlMinutes = (int) config('hotel.table_session_ttl_minutes', 360);
        $sameSite = (string) config('hotel.table_session_same_site', 'lax');

        $cookie = Cookie::make(
            $cookieName,
            (string) $order->session_token,
            $ttlMinutes,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            $sameSite
        );

        $request->session()->put($cookieName, (string) $order->session_token);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Order placed successfully.',
                'order_id' => $order->id,
                'redirect_url' => route('order.live-status', ['order' => $order->id]),
            ], 201)->withCookie($cookie);
        }

        return redirect()
            ->route('order.live-status', ['order' => $order->id])
            ->withCookie($cookie)
            ->with('status', 'Your order has been submitted and awaits staff confirmation.');
    }

    /**
     * Append add-on items as an incremental OrderBatch (#2, #3, ...) under an active Order.
     */
    public function appendItems(AppendOrderItemsRequest $request, Order $order): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        /** @var Order $updatedOrder */
        $updatedOrder = DB::transaction(function () use ($order, $validated, $request): Order {
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
            $isStaffAssisted = $request->user() !== null;

            $batch = OrderBatch::create([
                'order_id' => $lockedOrder->id,
                'batch_number' => $nextBatchNumber,
                'status' => $isStaffAssisted ? 'confirmed' : 'pending',
            ]);

            $this->billingService->attachItemsToBatchWithLock($lockedOrder, $batch, $validated['items']);

            if (! $isStaffAssisted && in_array($lockedOrder->order_status, ['confirmed', 'preparing', 'served'], true)) {
                $lockedOrder->order_status = 'pending_confirmation';
                $lockedOrder->save();
            } elseif ($isStaffAssisted && $lockedOrder->order_status === 'served') {
                $lockedOrder->order_status = 'preparing';
                $lockedOrder->save();
            }

            return $this->billingService->recalculateOrder($lockedOrder);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Add-on batch appended to order.',
                'order' => $this->formatLiveOrderPayload($updatedOrder),
            ]);
        }

        return redirect()
            ->back()
            ->with('status', 'Add-on items have been added to your order.');
    }

    /**
     * Display the live order tracking and itemized bill screen (or JSON payload for Alpine polling).
     */
    public function liveStatus(Request $request, Order $order): View|JsonResponse
    {
        $order->load([
            'restaurantTable',
            'batches.items.menuItem.category',
        ]);

        if ($request->expectsJson() || $request->boolean('poll')) {
            return response()->json($this->formatLiveOrderPayload($order));
        }

        $categories = Category::query()
            ->active()
            ->with(['menuItems' => function ($query): void {
                $query->where('is_available', true)->orderBy('name');
            }])
            ->get();

        return view('guest.live-bill', [
            'order' => $order,
            'categories' => $categories,
            'livePayload' => $this->formatLiveOrderPayload($order),
        ]);
    }

    /**
     * Display a tamper-proof signed digital receipt for SMS links.
     */
    public function digitalReceipt(Request $request, Order $order): View
    {
        if (! $this->hasValidProxyAwareSignature($request)) {
            abort(403, 'Invalid or expired receipt signature.');
        }

        $order->load([
            'restaurantTable',
            'batches.items.menuItem.category',
            'discountApprover',
        ]);

        return view('guest.live-bill', [
            'order' => $order,
            'categories' => collect(),
            'livePayload' => $this->formatLiveOrderPayload($order),
            'isReceiptView' => true,
        ]);
    }

    /**
     * Verify Laravel signed URL signatures across reverse proxies (HTTP/HTTPS) and LAN/localhost origins.
     */
    private function hasValidProxyAwareSignature(Request $request): bool
    {
        if ($request->hasValidSignature() || $request->hasValidSignature(false)) {
            return true;
        }

        $signature = (string) $request->query('signature', '');
        if ($signature === '') {
            return false;
        }

        $appKey = (string) config('app.key', '');
        if (str_starts_with($appKey, 'base64:')) {
            $appKey = (string) base64_decode(substr($appKey, 7), true);
        }

        $path = '/'.ltrim($request->getPathInfo(), '/');
        $queryWithoutSig = collect($request->query())
            ->except('signature')
            ->all();

        $queryString = http_build_query($queryWithoutSig);
        $relativeUri = $queryString !== '' ? "{$path}?{$queryString}" : $path;

        $host = $request->getHttpHost();
        $candidateRoots = array_unique(array_filter([
            "https://{$host}",
            "http://{$host}",
            rtrim((string) config('app.url'), '/'),
            rtrim((string) config('hotel.lan_url'), '/'),
            'http://127.0.0.1:8000',
            'http://localhost:8000',
        ]));

        foreach ($candidateRoots as $root) {
            $expected = hash_hmac('sha256', $root.$relativeUri, $appKey);
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format the order state for the 4-second Alpine.js polling endpoint.
     *
     * @return array<string, mixed>
     */
    private function formatLiveOrderPayload(Order $order): array
    {
        $order->loadMissing(['restaurantTable', 'batches.items.menuItem.category']);

        $grossSubtotal = (float) $order->gross_subtotal;
        $discountAmount = (float) $order->discount_amount;
        $netTaxable = max(0.0, round($grossSubtotal - $discountAmount, 2));

        return [
            'id' => $order->id,
            'table_number' => $order->restaurantTable?->table_number,
            'customer_name' => $order->customer_name,
            'customer_phone_masked' => $order->maskedPhone(),
            'order_status' => $order->order_status,
            'payment_method' => $order->payment_method,
            'gross_subtotal' => number_format($grossSubtotal, 2, '.', ''),
            'discount_percentage' => number_format((float) $order->discount_percentage, 2, '.', ''),
            'discount_amount' => number_format($discountAmount, 2, '.', ''),
            'net_taxable' => number_format($netTaxable, 2, '.', ''),
            'vat_rate' => number_format((float) $order->vat_rate, 2, '.', ''),
            'vat_amount' => number_format((float) $order->vat_amount, 2, '.', ''),
            'service_charge_rate' => number_format((float) $order->service_charge_rate, 2, '.', ''),
            'service_charge_amount' => number_format((float) $order->service_charge_amount, 2, '.', ''),
            'extra_charge_label' => $order->extra_charge_label,
            'extra_charge_amount' => number_format((float) ($order->extra_charge_amount ?? 0), 2, '.', ''),
            'grand_total' => number_format((float) $order->grand_total, 2, '.', ''),
            'updated_at' => $order->updated_at?->toIso8601String(),
            'batches' => $order->batches->map(function (OrderBatch $batch): array {
                return [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'status' => $batch->status,
                    'created_at_human' => $batch->created_at?->format('h:i A'),
                    'items' => $batch->items->map(function ($item): array {
                        return [
                            'id' => $item->id,
                            'name' => $item->menuItem?->name ?? 'Menu Item',
                            'station' => $item->menuItem?->category?->station ?? 'kitchen',
                            'quantity' => $item->quantity,
                            'unit_price' => number_format((float) $item->unit_price, 2, '.', ''),
                            'total_price' => number_format((float) $item->total_price, 2, '.', ''),
                            'notes' => $item->notes,
                            'item_status' => $item->item_status,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }
}
