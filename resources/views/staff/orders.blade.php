@extends('layouts.app')

@section('title', 'Sales Assistant Handheld Queue — ' . config('hotel.name'))

@section('content')
<div class="mx-auto max-w-6xl px-4 py-6"
     x-data="staffQueueApp({
         initialPayload: @js($initialPayload),
         pollUrl: @js(route('staff.dashboard', ['poll' => 1])),
         csrfToken: @js(csrf_token())
     })"
     x-init="initPoller()">

    <!-- Top Handheld Status Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-slate-800 bg-slate-900 p-5 shadow-xl">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Handheld Sales Assistant View &bull; 3s Live Polling</span>
            </div>
            <h1 class="mt-1 text-2xl font-extrabold text-white">Active Table Orders &amp; Station Routing</h1>
            <p class="text-xs text-slate-400">Last synced: <span x-text="payload.polled_at"></span></p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="button"
                    @click="soundEnabled = !soundEnabled; if (soundEnabled) playChime()"
                    :class="soundEnabled ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-300' : 'border-slate-700 bg-slate-800 text-slate-400'"
                    class="rounded-xl border px-3.5 py-2 text-xs font-bold transition">
                <span x-text="soundEnabled ? '🔊 Web Audio Chime: ON' : '🔇 Tap to Enable Audio Chime'"></span>
            </button>

            <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-2 text-center">
                <div class="text-[10px] font-bold uppercase tracking-wider text-amber-300">Pending Confirmation</div>
                <div class="text-lg font-extrabold text-amber-400" x-text="payload.pending_confirmation_count"></div>
            </div>
        </div>
    </div>

    <!-- Orders Grid -->
    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        <template x-for="order in payload.orders" :key="order.id">
            <div class="flex flex-col justify-between rounded-3xl border p-6 shadow-xl transition"
                 :class="order.needs_confirmation ? 'border-amber-500 bg-amber-500/5 ring-1 ring-amber-500/30' : 'border-slate-800 bg-slate-900'">

                <div>
                    <!-- Card Header -->
                    <div class="flex items-start justify-between gap-3 border-b border-slate-800 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-lg bg-amber-500 px-3 py-1 text-sm font-extrabold text-slate-950"
                                      x-text="'TABLE ' + order.table_number"></span>
                                <span class="font-mono text-xs text-slate-400" x-text="'#' + order.id"></span>
                                <span class="text-xs text-slate-500" x-text="order.created_at_human"></span>
                            </div>
                            <div class="mt-2 text-sm font-bold text-white">
                                <span x-text="order.customer_name"></span>
                                <span class="ml-2 font-mono text-xs text-slate-400" x-text="order.customer_phone"></span>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="inline-block rounded-full px-3 py-1 text-xs font-extrabold uppercase"
                                  :class="order.needs_confirmation ? 'bg-amber-500 text-slate-950' : 'bg-slate-800 text-emerald-300'"
                                  x-text="order.order_status.replace('_', ' ')"></span>
                            <div class="mt-1 text-xs font-bold text-slate-300">
                                Total: LKR <span x-text="order.grand_total"></span>
                            </div>
                        </div>
                    </div>

                    <!-- One-Tap Acknowledge & Confirm Button -->
                    <div x-show="order.needs_confirmation" class="mt-4">
                        <button type="button"
                                @click="confirmOrder(order.id)"
                                class="w-full rounded-2xl bg-amber-500 py-3 text-sm font-extrabold text-slate-950 shadow-lg transition hover:bg-amber-400">
                            ✓ Acknowledge &amp; Confirm Order (Route to Kitchen &amp; Bar)
                        </button>
                    </div>

                    <!-- Batches List with Kitchen vs Bar Routing -->
                    <div class="mt-4 space-y-3">
                        <template x-for="batch in order.batches" :key="batch.id">
                            <div class="rounded-2xl border border-slate-800 bg-slate-950/90 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800/80 pb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-extrabold text-white"
                                              x-text="'Batch #' + batch.batch_number"></span>
                                        <span class="rounded px-2 py-0.5 text-[10px] font-bold uppercase"
                                              :class="{
                                                  'bg-amber-500/20 text-amber-300': batch.status === 'pending',
                                                  'bg-sky-500/20 text-sky-300': batch.status === 'confirmed',
                                                  'bg-purple-500/20 text-purple-300': batch.status === 'preparing',
                                                  'bg-emerald-500/20 text-emerald-300': batch.status === 'ready',
                                                  'bg-slate-800 text-slate-400': batch.status === 'delivered'
                                              }"
                                              x-text="batch.status"></span>
                                    </div>

                                    <button type="button"
                                            x-show="batch.status === 'ready' || batch.status === 'confirmed'"
                                            @click="deliverBatch(batch.id)"
                                            class="rounded-xl bg-emerald-500 px-3 py-1 text-xs font-extrabold text-slate-950 transition hover:bg-emerald-400">
                                        Mark Batch Delivered &rarr;
                                    </button>
                                </div>

                                <!-- Routed Stations Breakdown -->
                                <div class="mt-2.5 space-y-1.5">
                                    <template x-for="item in batch.all_items" :key="item.id">
                                        <div class="flex items-center justify-between text-xs">
                                            <div class="flex items-center gap-2">
                                                <span class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase"
                                                      :class="item.station === 'bar' ? 'bg-sky-500/15 text-sky-300' : 'bg-orange-500/15 text-orange-300'"
                                                      x-text="item.station === 'bar' ? 'BAR' : 'KITCHEN'"></span>
                                                <span class="font-semibold text-white"
                                                      :class="item.item_status === 'cancelled' ? 'line-through text-rose-400' : ''"
                                                      x-text="item.quantity + 'x ' + item.name"></span>
                                                <span x-show="item.notes" class="italic text-amber-300" x-text="'(' + item.notes + ')'"></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] uppercase text-slate-400" x-text="item.item_status"></span>
                                                <button type="button"
                                                        x-show="item.item_status !== 'cancelled' && item.item_status !== 'served'"
                                                        @click="cancelItem(item.id)"
                                                        class="text-[10px] font-semibold text-rose-400 hover:underline">
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Staff Verbal Add-On Action Footer -->
                <div class="mt-4 flex items-center justify-between border-t border-slate-800 pt-3">
                    <span class="text-xs text-slate-400">Verbal table request?</span>
                    <button type="button"
                            @click="openStaffAddonModal(order)"
                            class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-1.5 text-xs font-bold text-amber-300 transition hover:bg-amber-500 hover:text-slate-950">
                        + Append Verbal Add-On Batch
                    </button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="payload.orders.length === 0" class="mt-8 rounded-3xl border border-slate-800 bg-slate-900 p-12 text-center">
        <p class="text-base font-bold text-slate-300">No active table orders right now.</p>
        <p class="mt-1 text-xs text-slate-500">New guest QR orders will appear automatically with an audio chime.</p>
    </div>

    <!-- Staff Verbal Add-On Modal -->
    <div x-show="addonModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div @click.away="addonModalOpen = false"
             class="max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-extrabold text-white">
                    Append Verbal Add-On &bull; Table <span x-text="selectedOrder ? selectedOrder.table_number : ''"></span>
                </h3>
                <button type="button" @click="addonModalOpen = false" class="text-xs text-slate-400 hover:text-white">Close &times;</button>
            </div>

            <form method="POST" :action="selectedOrder ? `/staff/orders/${selectedOrder.id}/append` : '#'" class="mt-4 space-y-4">
                @csrf
                <div class="max-h-72 space-y-2.5 overflow-y-auto pr-1">
                    @foreach ($categories as $category)
                        <div class="pt-2 text-xs font-bold uppercase tracking-wider text-amber-400">{{ $category->name }} ({{ strtoupper($category->station) }})</div>
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

                <template x-for="(item, idx) in addonItemsArray" :key="item.id">
                    <div>
                        <input type="hidden" :name="`items[${idx}][menu_item_id]`" :value="item.id">
                        <input type="hidden" :name="`items[${idx}][quantity]`" :value="item.quantity">
                    </div>
                </template>

                <button type="submit"
                        :disabled="addonItemsArray.length === 0"
                        class="w-full rounded-2xl bg-amber-500 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-amber-400 disabled:opacity-40">
                    Confirm &amp; Append Batch to Order
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function staffQueueApp(config) {
    return {
        payload: config.initialPayload,
        soundEnabled: true,
        lastPendingCount: config.initialPayload.pending_confirmation_count || 0,
        addonModalOpen: false,
        selectedOrder: null,
        staffAddons: {},

        initPoller() {
            setInterval(() => {
                this.fetchQueue();
            }, 3000);
        },

        fetchQueue() {
            fetch(config.pollUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.ok ? res.json() : null)
            .then(data => {
                if (!data) return;
                if (data.pending_confirmation_count > this.lastPendingCount && this.soundEnabled) {
                    this.playChime();
                }
                this.lastPendingCount = data.pending_confirmation_count;
                this.payload = data;
            })
            .catch(() => {});
        },

        playChime() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                osc.frequency.setValueAtTime(1174.66, ctx.currentTime + 0.12);
                gain.gain.setValueAtTime(0.18, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.45);
            } catch (e) {}
        },

        confirmOrder(orderId) {
            fetch(`/staff/orders/${orderId}/confirm`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.payload) this.payload = data.payload;
            });
        },

        deliverBatch(batchId) {
            fetch(`/staff/batches/${batchId}/deliver`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.payload) this.payload = data.payload;
            });
        },

        cancelItem(itemId) {
            if (!confirm('Cancel this item and restore stock?')) return;
            fetch(`/staff/items/${itemId}/cancel`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.payload) this.payload = data.payload;
            });
        },

        openStaffAddonModal(order) {
            this.selectedOrder = order;
            this.staffAddons = {};
            this.addonModalOpen = true;
        },

        get addonItemsArray() {
            return Object.values(this.staffAddons);
        },

        addonQty(id) {
            return this.staffAddons[id] ? this.staffAddons[id].quantity : 0;
        },

        incAddon(item) {
            if (!this.staffAddons[item.id]) {
                this.staffAddons[item.id] = { id: item.id, name: item.name, quantity: 1 };
            } else {
                this.staffAddons[item.id].quantity++;
            }
        },

        decAddon(id) {
            if (!this.staffAddons[id]) return;
            if (this.staffAddons[id].quantity <= 1) {
                delete this.staffAddons[id];
            } else {
                this.staffAddons[id].quantity--;
            }
        }
    };
}
</script>
@endpush
@endsection

