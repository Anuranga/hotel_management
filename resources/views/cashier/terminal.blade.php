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
         extraChargeAmount: {{ $selectedOrder ? (float) ($selectedOrder->extra_charge_amount ?? 0) : 0 }},
         extraChargeLabel: @js($selectedOrder?->extra_charge_label ?? ''),
         cashierItemModal: false,
         cashierAddons: {},
         get discountAmt() {
             return Math.round(this.grossSubtotal * (Math.max(0, Math.min(100, Number(this.discountPct) || 0)) / 100) * 100) / 100;
         },
         get netTaxable() {
             return Math.max(0, Math.round((this.grossSubtotal - this.discountAmt) * 100) / 100);
         },
         get serviceAmt() {
             return Math.round(this.netTaxable * (Math.max(0, Number(this.serviceRate) || 0) / 100) * 100) / 100;
         },
         get vatAmt() {
             return Math.round(this.netTaxable * (Math.max(0, Number(this.vatRate) || 0) / 100) * 100) / 100;
         },
         get extraAmt() {
             return Math.max(0, Math.round((Number(this.extraChargeAmount) || 0) * 100) / 100);
         },
         get grandTotal() {
             return Math.round((this.netTaxable + this.serviceAmt + this.vatAmt + this.extraAmt) * 100) / 100;
         },
         get cashierAddonArray() {
             return Object.values(this.cashierAddons);
         },
         addonQty(id) {
             return this.cashierAddons[id] ? this.cashierAddons[id].quantity : 0;
         },
         incAddon(item) {
             if (!this.cashierAddons[item.id]) {
                 this.cashierAddons[item.id] = { id: item.id, name: item.name, quantity: 1 };
             } else {
                 this.cashierAddons[item.id].quantity++;
             }
         },
         decAddon(id) {
             if (!this.cashierAddons[id]) return;
             if (this.cashierAddons[id].quantity <= 1) {
                 delete this.cashierAddons[id];
             } else {
                 this.cashierAddons[id].quantity--;
             }
         }
     }">

    <div class="grid gap-6 lg:grid-cols-12">
        <!-- Left Sidebar: Active Table Orders & Recent Settled Bills (No-Print) -->
        <div class="no-print space-y-6 lg:col-span-4">
            <div class="rounded-3xl border border-slate-800 bg-slate-900 p-5 shadow-xl">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-amber-400">
                    Active Unsettled Table Bills ({{ $activeOrders->count() }})
                </h2>
                <p class="mt-0.5 text-[11px] text-slate-400">Orders confirmed completed by Sales Assistants appear at the top.</p>

                <div class="mt-3 space-y-2.5">
                    @forelse ($activeOrders as $orderItem)
                        @php
                            $isReadyForBilling = $orderItem->bill_requested_at !== null || $orderItem->order_status === 'served';
                        @endphp
                        <a href="{{ route('cashier.billing', ['order_id' => $orderItem->id]) }}"
                           class="block rounded-2xl border p-3.5 transition {{ $selectedOrder && $selectedOrder->id === $orderItem->id ? 'border-amber-500 bg-amber-500/10' : ($isReadyForBilling ? 'border-emerald-500/50 bg-emerald-500/5' : 'border-slate-800 bg-slate-950/80 hover:border-slate-700') }}">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-extrabold text-white">
                                    Table {{ $orderItem->restaurantTable?->table_number }} &bull; #{{ $orderItem->id }}
                                </span>
                                @if ($isReadyForBilling)
                                    <span class="rounded-full bg-emerald-500 px-2.5 py-0.5 text-[10px] font-extrabold uppercase text-slate-950">
                                        🧾 Ready to Bill
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-[10px] font-bold uppercase text-amber-300">
                                        {{ str_replace('_', ' ', $orderItem->order_status) }}
                                    </span>
                                @endif
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

        <!-- Center: Unified Invoice Calculation, Service Charge, VAT, Extra Charges & Discount Gate (No-Print) -->
        <div class="no-print lg:col-span-5">
            @if ($selectedOrder)
                <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-lg bg-amber-500 px-2.5 py-1 text-xs font-extrabold text-slate-950">
                                    TABLE {{ $selectedOrder->restaurantTable?->table_number }}
                                </span>
                                @if ($selectedOrder->bill_requested_at || $selectedOrder->order_status === 'served')
                                    <span class="rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-[11px] font-extrabold text-emerald-300">
                                        ✓ Confirmed by Sales Assistant
                                    </span>
                                @else
                                    <span class="rounded-full bg-amber-500/20 px-2.5 py-0.5 text-[11px] font-bold text-amber-300">
                                        Status: {{ str_replace('_', ' ', $selectedOrder->order_status) }}
                                    </span>
                                @endif
                            </div>
                            <h1 class="mt-2 text-xl font-extrabold text-white">
                                Unified Invoice #{{ $selectedOrder->id }}
                            </h1>
                            <p class="text-xs text-slate-400">
                                Guest: {{ $selectedOrder->customer_name }} &bull; Phone: {{ $selectedOrder->displayPhoneFor($viewer) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($selectedOrder->isActive())
                                <button type="button"
                                        @click="cashierAddons = {}; cashierItemModal = true"
                                        class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-xs font-bold text-amber-300 hover:bg-amber-500 hover:text-slate-950">
                                    + Add Item
                                </button>
                            @endif
                            <button type="button"
                                    onclick="window.print()"
                                    class="rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-bold text-white hover:border-amber-500">
                                🖨️ Print 80mm
                            </button>
                        </div>
                    </div>

                    <!-- Compiled Batches Breakdown -->
                    <div class="mt-4 max-h-60 space-y-3 overflow-y-auto pr-1">
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
                        <!-- Charges, Service Charge, Tax/VAT, Discount & Settlement Form -->
                        <form method="POST" action="{{ route('cashier.orders.settle', ['order' => $selectedOrder->id]) }}" class="mt-5 space-y-4 border-t border-slate-800 pt-4">
                            @csrf

                            <!-- Row 1: Service Charge (%) & Tax / VAT (%) -->
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-bold uppercase tracking-wider text-slate-300">Service Charge %</label>
                                        <div class="flex gap-1">
                                            <button type="button" @click="serviceRate = 0" class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-bold text-slate-300 hover:bg-amber-500 hover:text-slate-950">0%</button>
                                            <button type="button" @click="serviceRate = 5" class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-bold text-slate-300 hover:bg-amber-500 hover:text-slate-950">5%</button>
                                            <button type="button" @click="serviceRate = 10" class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-bold text-slate-300 hover:bg-amber-500 hover:text-slate-950">10%</button>
                                        </div>
                                    </div>
                                    <input type="number" step="0.01" min="0" max="100"
                                           name="service_charge_rate"
                                           x-model.number="serviceRate"
                                           class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm font-mono text-white focus:border-amber-500 focus:outline-none">
                                    <div class="mt-1 text-[11px] text-slate-400">
                                        Amount: <span class="font-mono font-bold text-white">LKR <span x-text="serviceAmt.toFixed(2)"></span></span>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-bold uppercase tracking-wider text-slate-300">Tax / VAT %</label>
                                        <div class="flex gap-1">
                                            <button type="button" @click="vatRate = 0" class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-bold text-slate-300 hover:bg-amber-500 hover:text-slate-950">0%</button>
                                            <button type="button" @click="vatRate = 15" class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-bold text-slate-300 hover:bg-amber-500 hover:text-slate-950">15%</button>
                                            <button type="button" @click="vatRate = 18" class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-bold text-slate-300 hover:bg-amber-500 hover:text-slate-950">18%</button>
                                        </div>
                                    </div>
                                    <input type="number" step="0.01" min="0" max="100"
                                           name="vat_rate"
                                           x-model.number="vatRate"
                                           class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm font-mono text-white focus:border-amber-500 focus:outline-none">
                                    <div class="mt-1 text-[11px] text-slate-400">
                                        Amount: <span class="font-mono font-bold text-white">LKR <span x-text="vatAmt.toFixed(2)"></span></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2: Extra Charges (Corkage / Packaging / Room Service / Custom Fee) -->
                            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-amber-400">
                                    + Extra Charges (Corkage, Packaging, Room Service, Custom Fee)
                                </label>
                                <div class="mt-2 grid gap-2.5 sm:grid-cols-12">
                                    <div class="sm:col-span-7">
                                        <input type="text"
                                               name="extra_charge_label"
                                               x-model="extraChargeLabel"
                                               maxlength="100"
                                               placeholder="Description (e.g., Corkage / Packaging / Extra Service)"
                                               class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-none">
                                    </div>
                                    <div class="sm:col-span-5">
                                        <input type="number" step="0.01" min="0"
                                               name="extra_charge_amount"
                                               x-model.number="extraChargeAmount"
                                               placeholder="Amount (LKR)"
                                               class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs font-mono text-white focus:border-amber-500 focus:outline-none">
                                    </div>
                                </div>
                            </div>

                            <!-- Row 3: Discount % & Manager PIN Gate (>10%) -->
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                        Discount % (Promo &le; {{ $maxStandardDiscount }}%)
                                    </label>
                                    <input type="number" step="0.01" min="0" max="100"
                                           name="discount_percentage"
                                           x-model.number="discountPct"
                                           class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm font-mono text-white focus:border-amber-500 focus:outline-none">
                                </div>

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

                            <!-- Live Calculated Summary Banner -->
                            <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-3.5 text-xs space-y-1">
                                <div class="flex justify-between">
                                    <span>Net Taxable (After Discount):</span>
                                    <span class="font-mono font-bold">LKR <span x-text="netTaxable.toFixed(2)"></span></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Service Charge (<span x-text="serviceRate || 0"></span>%) + VAT (<span x-text="vatRate || 0"></span>%):</span>
                                    <span class="font-mono font-bold">LKR <span x-text="(serviceAmt + vatAmt).toFixed(2)"></span></span>
                                </div>
                                <div class="flex justify-between" x-show="extraAmt > 0">
                                    <span x-text="extraChargeLabel || 'Extra Charges'"></span>
                                    <span class="font-mono font-bold">LKR <span x-text="extraAmt.toFixed(2)"></span></span>
                                </div>
                                <div class="flex justify-between border-t border-amber-500/30 pt-1.5 text-sm font-extrabold text-amber-400">
                                    <span>LIVE PAYABLE GRAND TOTAL:</span>
                                    <span class="font-mono">LKR <span x-text="grandTotal.toFixed(2)"></span></span>
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
                                    Update Bill Charges
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
                        <div class="text-[10px]">Sri Lanka &bull; Official Tax Invoice</div>
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
                        @if ((float) ($selectedOrder->extra_charge_amount ?? 0) > 0)
                            <div class="flex justify-between">
                                <span>{{ $selectedOrder->extra_charge_label ?: 'Extra Charge' }}:</span>
                                <span>{{ number_format((float) $selectedOrder->extra_charge_amount, 2) }}</span>
                            </div>
                        @endif
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

    <!-- Cashier Add Extra Menu Item Modal -->
    @if ($selectedOrder && $selectedOrder->isActive())
        <div x-show="cashierItemModal"
             x-cloak
             class="no-print fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
            <div class="max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-extrabold text-white">Add Menu Items to Invoice #{{ $selectedOrder->id }}</h3>
                    <button type="button" @click="cashierItemModal = false" class="text-xs text-slate-400 hover:text-white">Close &times;</button>
                </div>

                <form method="POST" action="{{ route('cashier.orders.append', ['order' => $selectedOrder->id]) }}" class="mt-4 space-y-4">
                    @csrf
                    <div class="max-h-72 space-y-2.5 overflow-y-auto pr-1">
                        @foreach ($categories as $category)
                            <div class="pt-2 text-xs font-bold uppercase tracking-wider text-amber-400">{{ $category->name }}</div>
                            @foreach ($category->menuItems as $item)
                                <div class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-950 px-3 py-2">
                                    <div>
                                        <div class="text-xs font-bold text-white">{{ $item->name }}</div>
                                        <div class="text-[11px] text-slate-400">LKR {{ number_format((float) $item->price, 2) }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="decAddon({{ $item->id }})" class="h-7 w-7 rounded-lg bg-slate-800 text-xs font-bold text-white">&minus;</button>
                                        <span class="w-5 text-center text-xs font-bold text-white" x-text="addonQty({{ $item->id }})"></span>
                                        <button type="button" @click="incAddon({ id: {{ $item->id }}, name: @js($item->name) })" class="h-7 w-7 rounded-lg bg-amber-500 text-xs font-bold text-slate-950">+</button>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>

                    <template x-for="(item, idx) in cashierAddonArray" :key="item.id">
                        <div>
                            <input type="hidden" :name="`items[${idx}][menu_item_id]`" :value="item.id">
                            <input type="hidden" :name="`items[${idx}][quantity]`" :value="item.quantity">
                        </div>
                    </template>

                    <button type="submit"
                            :disabled="cashierAddonArray.length === 0"
                            class="w-full rounded-2xl bg-amber-500 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-amber-400 disabled:opacity-40">
                        + Append Selected Items to Bill
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
