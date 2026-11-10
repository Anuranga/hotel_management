@extends('layouts.app')

@section('title', 'Order #' . $order->id . ' — Live Status & Digital Bill')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-6 pb-28"
     x-data="liveOrderTracker({
         initialOrder: @js($livePayload),
         pollUrl: @js(route('order.live-status', ['order' => $order->id, 'poll' => 1])),
         isReceiptView: {{ !empty($isReceiptView) ? 'true' : 'false' }}
     })"
     x-init="startPolling()">

    <!-- Header Card -->
    <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="rounded-lg bg-amber-500 px-3 py-1 text-xs font-extrabold text-slate-950">
                    TABLE <span x-text="order.table_number"></span>
                </span>
                <span class="text-xs font-mono text-slate-400">ORDER #<span x-text="order.id"></span></span>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-block h-2 w-2 rounded-full bg-emerald-400 animate-ping" x-show="order.order_status !== 'settled'"></span>
                <span class="rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-wider"
                      :class="statusBadgeClass(order.order_status)"
                      x-text="order.order_status.replace('_', ' ')"></span>
            </div>
        </div>

        <h1 class="mt-3 text-xl font-extrabold text-white">
            Guest: <span x-text="order.customer_name"></span>
        </h1>
        <p class="text-xs text-slate-400">
            Phone: <span x-text="order.customer_phone_masked"></span> &bull; Auto-refreshing every 4 seconds
        </p>

        <!-- Order Progress Bar -->
        <div class="mt-5 grid grid-cols-4 gap-2 text-center text-[11px] font-semibold">
            <div class="rounded-xl p-2" :class="stepActive(1) ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-slate-950 text-slate-500'">
                1. Pending
            </div>
            <div class="rounded-xl p-2" :class="stepActive(2) ? 'bg-sky-500/20 text-sky-300 border border-sky-500/40' : 'bg-slate-950 text-slate-500'">
                2. Confirmed
            </div>
            <div class="rounded-xl p-2" :class="stepActive(3) ? 'bg-purple-500/20 text-purple-300 border border-purple-500/40' : 'bg-slate-950 text-slate-500'">
                3. Preparing
            </div>
            <div class="rounded-xl p-2" :class="stepActive(4) ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-slate-950 text-slate-500'">
                4. Served / Settled
            </div>
        </div>
    </div>

    <!-- Batch-by-Batch Breakdown -->
    <div class="mt-6 space-y-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Order Batches &amp; Item Status</h2>

        <template x-for="batch in order.batches" :key="batch.id">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-5">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <span class="text-sm font-extrabold text-white"
                              x-text="batch.batch_number === 1 ? 'Batch #1 (Initial Order)' : 'Batch #' + batch.batch_number + ' (Add-on)'"></span>
                        <span class="ml-2 text-xs text-slate-400" x-text="batch.created_at_human"></span>
                    </div>
                    <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs font-semibold uppercase text-amber-300"
                          x-text="batch.status"></span>
                </div>

                <div class="mt-3 divide-y divide-slate-800/60">
                    <template x-for="item in batch.items" :key="item.id">
                        <div class="flex items-center justify-between py-2.5 text-xs">
                            <div>
                                <div class="font-bold text-white">
                                    <span x-text="item.quantity"></span>&times; <span x-text="item.name"></span>
                                    <span class="ml-1.5 rounded bg-slate-800 px-1.5 py-0.5 text-[10px] uppercase text-slate-300" x-text="item.item_status"></span>
                                </div>
                                <div x-show="item.notes" class="mt-0.5 italic text-amber-300" x-text="'Note: ' + item.notes"></div>
                            </div>
                            <div class="font-bold text-slate-200" :class="item.item_status === 'cancelled' ? 'line-through text-rose-400' : ''">
                                LKR <span x-text="item.total_price"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- Running Financial Bill Breakdown -->
    <div class="mt-6 rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Live Running Bill Breakdown</h2>

        <div class="mt-4 space-y-2 text-xs">
            <div class="flex justify-between text-slate-300">
                <span>Gross Subtotal</span>
                <span>LKR <span x-text="order.gross_subtotal"></span></span>
            </div>

            <div class="flex justify-between text-emerald-400" x-show="Number(order.discount_amount) > 0">
                <span>Applied Discount (<span x-text="order.discount_percentage"></span>%)</span>
                <span>&minus; LKR <span x-text="order.discount_amount"></span></span>
            </div>

            <div class="flex justify-between text-slate-300">
                <span>Net Taxable Amount</span>
                <span>LKR <span x-text="order.net_taxable"></span></span>
            </div>

            <div class="flex justify-between text-slate-400">
                <span>Service Charge (<span x-text="order.service_charge_rate"></span>%)</span>
                <span>LKR <span x-text="order.service_charge_amount"></span></span>
            </div>

            <div class="flex justify-between text-slate-400">
                <span>VAT (<span x-text="order.vat_rate"></span>%)</span>
                <span>LKR <span x-text="order.vat_amount"></span></span>
            </div>

            <div class="flex justify-between text-amber-300" x-show="Number(order.extra_charge_amount) > 0">
                <span x-text="order.extra_charge_label || 'Extra Charges'"></span>
                <span>+ LKR <span x-text="order.extra_charge_amount"></span></span>
            </div>

            <div class="flex justify-between border-t border-slate-800 pt-3 text-base font-extrabold text-amber-400">
                <span>Grand Total</span>
                <span>LKR <span x-text="order.grand_total"></span></span>
            </div>
        </div>
    </div>

    <!-- Floating Add-On Drawer Trigger (when order is still active) -->
    @if ($order->isActive() && empty($isReceiptView))
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-800 bg-slate-900/95 p-4 backdrop-blur"
             x-show="order.order_status !== 'settled' && order.order_status !== 'cancelled'">
            <div class="mx-auto flex max-w-2xl items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-white">Need water, desserts, or extra portions?</div>
                    <div class="text-[11px] text-slate-400">Appends a new batch under this same bill</div>
                </div>
                <button type="button"
                        @click="addonModal = true"
                        class="rounded-2xl bg-amber-500 px-5 py-2.5 text-xs font-extrabold text-slate-950 shadow transition hover:bg-amber-400">
                    + Order Add-on Items
                </button>
            </div>
        </div>

        <!-- Add-on Selection Modal -->
        <div x-show="addonModal"
             x-cloak
             class="fixed inset-0 z-50 flex flex-col justify-end bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="addonModal = false"
                 class="mx-auto max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-t-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-extrabold text-white">Append Add-On Items to Order #{{ $order->id }}</h3>
                    <button type="button" @click="addonModal = false" class="text-xs text-slate-400 hover:text-white">Close &times;</button>
                </div>

                <form method="POST" action="{{ route('order.append', ['order' => $order->id]) }}" class="mt-4 space-y-4"
                      data-confirm-type="success"
                      data-confirm-badge="Add-On Batch Confirmation"
                      data-confirm-title="Submit Add-On Items to Order #{{ $order->id }}?"
                      data-confirm-message="These items will be appended as a new batch under your current table bill and sent to the kitchen/bar."
                      data-confirm-button="✓ Yes, Order Add-On Items">
                    @csrf
                    <div class="max-h-80 space-y-3 overflow-y-auto pr-1">
                        @foreach ($categories as $category)
                            <div class="text-xs font-bold uppercase tracking-wider text-amber-400">{{ $category->name }}</div>
                            @foreach ($category->menuItems as $item)
                                <div class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-950 p-3">
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

                    <template x-for="(addon, idx) in addonArray" :key="addon.id">
                        <div>
                            <input type="hidden" :name="`items[${idx}][menu_item_id]`" :value="addon.id">
                            <input type="hidden" :name="`items[${idx}][quantity]`" :value="addon.quantity">
                        </div>
                    </template>

                    <button type="submit"
                            :disabled="addonArray.length === 0"
                            class="w-full rounded-2xl bg-amber-500 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-amber-400 disabled:opacity-40">
                        Submit Add-On Batch (<span x-text="addonArray.reduce((s, i) => s + i.quantity, 0)"></span> items)
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function liveOrderTracker(config) {
    return {
        order: config.initialOrder,
        addonModal: false,
        addons: {},
        poller: null,

        startPolling() {
            if (config.isReceiptView) return;
            this.poller = setInterval(() => {
                fetch(config.pollUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.ok ? res.json() : null)
                .then(data => {
                    if (data && data.id) {
                        this.order = data;
                    }
                })
                .catch(() => {});
            }, 4000);
        },

        stepActive(step) {
            const map = {
                'pending_confirmation': 1,
                'confirmed': 2,
                'preparing': 3,
                'served': 4,
                'settled': 4,
                'cancelled': 0
            };
            return (map[this.order.order_status] || 0) >= step;
        },

        statusBadgeClass(status) {
            return {
                'pending_confirmation': 'border-amber-500/40 bg-amber-500/15 text-amber-300',
                'confirmed': 'border-sky-500/40 bg-sky-500/15 text-sky-300',
                'preparing': 'border-purple-500/40 bg-purple-500/15 text-purple-300',
                'served': 'border-emerald-500/40 bg-emerald-500/15 text-emerald-300',
                'settled': 'border-emerald-500/40 bg-emerald-500/20 text-emerald-200',
                'cancelled': 'border-rose-500/40 bg-rose-500/15 text-rose-300'
            }[status] || 'border-slate-700 bg-slate-800 text-slate-300';
        },

        get addonArray() {
            return Object.values(this.addons);
        },

        addonQty(id) {
            return this.addons[id] ? this.addons[id].quantity : 0;
        },

        incAddon(item) {
            if (!this.addons[item.id]) {
                this.addons[item.id] = { id: item.id, name: item.name, quantity: 1 };
            } else {
                this.addons[item.id].quantity++;
            }
        },

        decAddon(id) {
            if (!this.addons[id]) return;
            if (this.addons[id].quantity <= 1) {
                delete this.addons[id];
            } else {
                this.addons[id].quantity--;
            }
        }
    };
}
</script>
@endpush
@endsection

