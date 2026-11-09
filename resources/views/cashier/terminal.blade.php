@extends('layouts.app')

@section('title', 'Cashier Settlement & Billing Terminal — ' . $hotelName)

@push('styles')
<style>
    @media print {
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            background: #ffffff !important;
            color: #000000 !important;
            font-family: 'Courier New', Courier, monospace !important;
            width: 80mm !important;
            margin: 0 auto !important;
        }
        .thermal-receipt {
            width: 80mm !important;
            max-width: 80mm !important;
            padding: 4mm !important;
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
            background: #ffffff !important;
            color: #000000 !important;
        }
        .thermal-receipt * {
            color: #000000 !important;
            border-color: #000000 !important;
        }
    }
</style>
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6"
     x-data="{
         discountPct: {{ $selectedOrder ? (float) $selectedOrder->discount_percentage : 0 }},
         maxStandard: {{ $maxStandardDiscount }},
         managerPin: '',
         paymentMethod: '{{ $selectedOrder && $selectedOrder->payment_method !== 'unpaid' ? $selectedOrder->payment_method : 'cash' }}',
         grossSubtotal: {{ $selectedOrder ? (float) $selectedOrder->gross_subtotal : 0 }},
         vatRate: {{ $selectedOrder ? (float) $selectedOrder->vat_rate : (float) config('hotel.vat_rate', 18) }},
         serviceRate: {{ $selectedOrder ? (float) $selectedOrder->service_charge_rate : (float) config('hotel.service_charge_rate', 10) }},
         get discountAmt() {
             return Math.round(this.grossSubtotal * (Math.max(0, Math.min(100, this.discountPct)) / 100) * 100) / 100;
         },
         get netTaxable() {
             return Math.max(0, Math.round((this.grossSubtotal - this.discountAmt) * 100) / 100);
         },
         get serviceAmt() {
             return Math.round(this.netTaxable * (this.serviceRate / 100) * 100) / 100;
         },
         get vatAmt() {
             return Math.round(this.netTaxable * (this.vatRate / 100) * 100) / 100;
         },
         get grandTotal() {
             return Math.round((this.netTaxable + this.serviceAmt + this.vatAmt) * 100) / 100;
         }
     }">

    <div class="grid gap-6 lg:grid-cols-12">
        <!-- Left Sidebar: Active Table Orders & Recent Settled Bills (No-Print) -->
        <div class="no-print space-y-6 lg:col-span-4">
            <div class="rounded-3xl border border-slate-800 bg-slate-900 p-5 shadow-xl">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-amber-400">
                    Active Unsettled Table Bills ({{ $activeOrders->count() }})
                </h2>
                <div class="mt-3 space-y-2.5">
                    @forelse ($activeOrders as $orderItem)
                        <a href="{{ route('cashier.billing', ['order_id' => $orderItem->id]) }}"
                           class="block rounded-2xl border p-3.5 transition {{ $selectedOrder && $selectedOrder->id === $orderItem->id ? 'border-amber-500 bg-amber-500/10' : 'border-slate-800 bg-slate-950/80 hover:border-slate-700' }}">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-extrabold text-white">
                                    Table {{ $orderItem->restaurantTable?->table_number }} &bull; #{{ $orderItem->id }}
                                </span>
                                <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-[10px] font-bold uppercase text-amber-300">
                                    {{ str_replace('_', ' ', $orderItem->order_status) }}
                                </span>
                            </div>
                            <div class="mt-1 flex items-center justify-between text-xs text-slate-400">
                                <span>{{ $orderItem->customer_name }} ({{ $orderItem->displayPhoneFor($viewer) }})</span>
                                <span class="font-extrabold text-emerald-400">LKR {{ number_format((float) $orderItem->grand_total, 2) }}</span>
                            </div>
                        </a>
                    @empty
                        <p class="py-4 text-center text-xs text-slate-500">No active unsettled orders.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl border border-slate-800 bg-slate-900 p-5 shadow-xl">
                <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">
                    Recently Settled Receipts
                </h2>
                <div class="mt-3 space-y-2">
                    @foreach ($recentSettledOrders as $settled)
                        <a href="{{ route('cashier.billing', ['order_id' => $settled->id]) }}"
                           class="flex items-center justify-between rounded-xl border p-3 text-xs transition {{ $selectedOrder && $selectedOrder->id === $settled->id ? 'border-emerald-500 bg-emerald-500/10' : 'border-slate-800 bg-slate-950 hover:border-slate-700' }}">
                            <div>
                                <div class="font-bold text-white">#{{ $settled->id }} &bull; Table {{ $settled->restaurantTable?->table_number }}</div>
                                <div class="text-[11px] text-slate-400">{{ strtoupper($settled->payment_method) }} &bull; {{ $settled->settled_at?->format('h:i A') }}</div>
                            </div>
                            <span class="font-bold text-emerald-400">LKR {{ number_format((float) $settled->grand_total, 2) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Center: Unified Invoice Calculation & Discount Override Gate (No-Print) -->
        <div class="no-print lg:col-span-5">
            @if ($selectedOrder)
                <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                        <div>
                            <span class="rounded-lg bg-amber-500 px-2.5 py-1 text-xs font-extrabold text-slate-950">
                                TABLE {{ $selectedOrder->restaurantTable?->table_number }}
                            </span>
                            <h1 class="mt-2 text-xl font-extrabold text-white">
                                Unified Invoice #{{ $selectedOrder->id }}
                            </h1>
                            <p class="text-xs text-slate-400">
                                Guest: {{ $selectedOrder->customer_name }} &bull; Phone: {{ $selectedOrder->displayPhoneFor($viewer) }}
                            </p>
                        </div>
                        <button type="button"
                                onclick="window.print()"
                                class="rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-bold text-white hover:border-amber-500">
                            🖨️ Print 80mm Receipt
                        </button>
                    </div>

                    <!-- Compiled Batches Breakdown -->
                    <div class="mt-4 max-h-72 space-y-3 overflow-y-auto pr-1">
                        @foreach ($selectedOrder->batches as $batch)
                            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3.5">
                                <div class="flex items-center justify-between text-xs font-bold text-amber-400">
                                    <span>Batch #{{ $batch->batch_number }} {{ $batch->batch_number === 1 ? '(Initial)' : '(Add-on)' }}</span>
                                    <span class="uppercase text-slate-400">{{ $batch->status }}</span>
                                </div>
                                <div class="mt-2 divide-y divide-slate-800/60">
                                    @foreach ($batch->items as $item)
                                        <div class="flex items-center justify-between py-1.5 text-xs">
                                            <div class="{{ $item->item_status === 'cancelled' ? 'line-through text-rose-400' : 'text-slate-200' }}">
                                                <span class="font-bold">{{ $item->quantity }}&times;</span> {{ $item->menuItem?->name }}
                                                @if ($item->menuItem?->is_vat_exempt)
                                                    <span class="ml-1 rounded bg-emerald-500/15 px-1 py-0.5 text-[9px] text-emerald-300">VAT Exempt</span>
                                                @endif
                                            </div>
                                            <div class="font-mono font-bold {{ $item->item_status === 'cancelled' ? 'line-through text-rose-400' : 'text-white' }}">
                                                LKR {{ number_format((float) $item->total_price, 2) }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($selectedOrder->isActive())
                        <!-- Discount & Settlement Form -->
                        <form method="POST" action="{{ route('cashier.orders.settle', ['order' => $selectedOrder->id]) }}" class="mt-5 space-y-4 border-t border-slate-800 pt-4">
                            @csrf

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                        Discount % (Promo &le; {{ $maxStandardDiscount }}%)
                                    </label>
                                    <input type="number" step="0.01" min="0" max="100"
                                           name="discount_percentage"
                                           x-model.number="discountPct"
                                           class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-white focus:border-amber-500 focus:outline-none">
                                </div>

                                <!-- Manager/Admin PIN Gate when Discount > 10% -->
                                <div x-show="discountPct > maxStandard" x-transition>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-amber-400">
                                        Manager / Admin PIN Required *
                                    </label>
                                    <input type="password"
                                           name="manager_pin"
                                           x-model="managerPin"
                                           placeholder="Enter Manager PIN (e.g. 1234)"
                                           class="mt-1 w-full rounded-xl border border-amber-500 bg-slate-950 px-3.5 py-2 text-sm text-white focus:outline-none">
                                </div>
                            </div>

                            <!-- Payment Method Selector: Cash, Card, QR Merchant Pay (online) -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">Payment Method</label>
                                <div class="mt-1.5 grid grid-cols-3 gap-2">
                                    <label class="cursor-pointer rounded-xl border p-2.5 text-center text-xs font-bold transition"
                                           :class="paymentMethod === 'cash' ? 'border-amber-500 bg-amber-500 text-slate-950' : 'border-slate-800 bg-slate-950 text-slate-300'">
                                        <input type="radio" name="payment_method" value="cash" x-model="paymentMethod" class="sr-only">
                                        💵 Cash
                                    </label>

                                    <label class="cursor-pointer rounded-xl border p-2.5 text-center text-xs font-bold transition"
                                           :class="paymentMethod === 'card' ? 'border-amber-500 bg-amber-500 text-slate-950' : 'border-slate-800 bg-slate-950 text-slate-300'">
                                        <input type="radio" name="payment_method" value="card" x-model="paymentMethod" class="sr-only">
                                        💳 Card
                                    </label>

                                    <label class="cursor-pointer rounded-xl border p-2.5 text-center text-xs font-bold transition"
                                           :class="paymentMethod === 'online' ? 'border-amber-500 bg-amber-500 text-slate-950' : 'border-slate-800 bg-slate-950 text-slate-300'">
                                        <input type="radio" name="payment_method" value="online" x-model="paymentMethod" class="sr-only">
                                        📱 QR Pay
                                    </label>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button type="submit"
                                        formaction="{{ route('cashier.orders.discount', ['order' => $selectedOrder->id]) }}"
                                        class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-3 text-xs font-bold text-slate-200 hover:border-amber-500">
                                    Save Discount Only
                                </button>

                                <button type="submit"
                                        class="grow rounded-xl bg-emerald-500 px-5 py-3 text-sm font-extrabold text-slate-950 shadow-lg transition hover:bg-emerald-400">
                                    ✓ Settle Invoice &amp; Dispatch SMS Receipt
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="mt-5 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-xs text-emerald-200">
                            <div class="font-extrabold uppercase">Invoice Settled ({{ strtoupper($selectedOrder->payment_method) }})</div>
                            <div class="mt-1">Settled At: {{ $selectedOrder->settled_at?->format('Y-m-d h:i:s A') }}</div>
                            @if ($selectedOrder->discountApprover)
                                <div>Discount Override Approved By: {{ $selectedOrder->discountApprover->name }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            @else
                <div class="rounded-3xl border border-slate-800 bg-slate-900 p-12 text-center">
                    <p class="text-sm font-bold text-slate-300">Select an active or settled order from the left panel.</p>
                </div>
            @endif
        </div>

        <!-- Right Column: 80mm Thermal Print Receipt Preview (Visible on Screen & Print) -->
        <div class="lg:col-span-3">
            @if ($selectedOrder)
                @php
                    $netTaxableStored = max(0, round((float) $selectedOrder->gross_subtotal - (float) $selectedOrder->discount_amount, 2));
                @endphp
                <div class="thermal-receipt mx-auto w-full max-w-[302px] rounded-2xl border border-slate-800 bg-white p-5 font-mono text-xs text-slate-950 shadow-xl">
                    <div class="border-b border-dashed border-slate-400 pb-3 text-center">
                        <div class="text-sm font-extrabold uppercase">{{ $hotelName }}</div>
                        <div class="text-[10px]">Colombo 01, Sri Lanka &bull; Tax Invoice</div>
                        <div class="mt-1 text-[10px]">
                            Order #{{ $selectedOrder->id }} &bull; Table {{ $selectedOrder->restaurantTable?->table_number }}
                        </div>
                        <div class="text-[10px]">
                            Date: {{ ($selectedOrder->settled_at ?? $selectedOrder->created_at)?->format('Y-m-d H:i') }}
                        </div>
                        <div class="text-[10px]">
                            Guest: {{ $selectedOrder->customer_name }} ({{ $selectedOrder->displayPhoneFor($viewer) }})
                        </div>
                    </div>

                    <div class="my-3 space-y-2 border-b border-dashed border-slate-400 pb-3">
                        @foreach ($selectedOrder->batches as $batch)
                            <div class="text-[10px] font-bold uppercase">-- Batch #{{ $batch->batch_number }} --</div>
                            @foreach ($batch->items->where('item_status', '!=', 'cancelled') as $item)
                                <div class="flex justify-between text-[11px]">
                                    <span>{{ $item->quantity }}x {{ $item->menuItem?->name }}</span>
                                    <span>{{ number_format((float) $item->total_price, 2) }}</span>
                                </div>
                            @endforeach
                        @endforeach
                    </div>

                    <div class="space-y-1 text-[11px]">
                        <div class="flex justify-between">
                            <span>Gross Subtotal:</span>
                            <span>{{ number_format((float) $selectedOrder->gross_subtotal, 2) }}</span>
                        </div>
                        @if ((float) $selectedOrder->discount_amount > 0)
                            <div class="flex justify-between">
                                <span>Discount ({{ number_format((float) $selectedOrder->discount_percentage, 2) }}%):</span>
                                <span>-{{ number_format((float) $selectedOrder->discount_amount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-bold">
                            <span>Net Taxable:</span>
                            <span>{{ number_format($netTaxableStored, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Service Chg ({{ number_format((float) $selectedOrder->service_charge_rate, 2) }}%):</span>
                            <span>{{ number_format((float) $selectedOrder->service_charge_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>VAT ({{ number_format((float) $selectedOrder->vat_rate, 2) }}%):</span>
                            <span>{{ number_format((float) $selectedOrder->vat_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-dashed border-slate-400 pt-1.5 text-sm font-extrabold">
                            <span>GRAND TOTAL:</span>
                            <span>LKR {{ number_format((float) $selectedOrder->grand_total, 2) }}</span>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-dashed border-slate-400 pt-3 text-center text-[10px]">
                        <div>Payment: {{ strtoupper($selectedOrder->payment_method) }}</div>
                        <div class="mt-1">Thank you for dining with us!</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

