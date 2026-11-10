@extends('layouts.app')

@section('title', 'Sales Assistant Handheld Queue — ' . config('hotel.name'))

@section('content')
<div class="mx-auto max-w-6xl px-4 py-6"
     x-data="staffQueueApp({
         initialPayload: @js($initialPayload),
         pollUrl: @js(route('staff.dashboard', ['poll' => 1])),
         csrfToken: @js(csrf_token()),
         firstTableId: {{ $tables->first()?->id ?? 1 }}
     })"
     x-init="initPoller()">

    <!-- Top Handheld Status Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-slate-800 bg-slate-900 p-5 shadow-xl">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Handheld Sales Assistant View &bull; 3s Live Polling</span>
            </div>
            <h1 class="mt-1 text-2xl font-extrabold text-white">Active Table Orders, Walk-In Ordering &amp; Billing Dispatch</h1>
            <p class="text-xs text-slate-400">Last synced: <span x-text="payload.polled_at"></span></p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Create New Assisted / Walk-In Order Button (For Customers Without QR Phone) -->
            <button type="button"
                    @click="openWalkInModal()"
                    class="rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-extrabold text-slate-950 shadow-lg transition hover:bg-amber-400">
                + Create Assisted / Walk-In Order
            </button>

            <button type="button"
                    @click="soundEnabled = !soundEnabled; if (soundEnabled) playChime()"
                    :class="soundEnabled ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-300' : 'border-slate-700 bg-slate-800 text-slate-400'"
                    class="rounded-xl border px-3.5 py-2 text-xs font-bold transition">
                <span x-text="soundEnabled ? '🔊 Chime: ON' : '🔇 Chime: OFF'"></span>
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
                            <div class="flex flex-wrap items-center gap-2">
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
                            <div x-show="order.is_sent_to_cashier" class="mt-1 text-[10px] font-extrabold uppercase text-emerald-400">
                                ✓ Sent to Cashier Billing
                            </div>
                        </div>
                    </div>

                    <!-- One-Tap Acknowledge & Confirm Button -->
                    <div x-show="order.needs_confirmation" class="mt-4">
                        <button type="button"
                                @click="confirmOrder(order)"
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
                                            @click="deliverBatch(order, batch)"
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
                                                        @click="cancelItem(order, item)"
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

                <!-- Staff Verbal Add-On & Final Send-to-Cashier Action Footer -->
                <div class="mt-4 space-y-2.5 border-t border-slate-800 pt-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <button type="button"
                                @click="openStaffAddonModal(order)"
                                class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-3.5 py-2 text-xs font-bold text-amber-300 transition hover:bg-amber-500 hover:text-slate-950">
                            + Add Verbal Items to Order
                        </button>

                        <button type="button"
                                @click="sendToCashier(order)"
                                class="rounded-xl bg-emerald-500 px-4 py-2 text-xs font-extrabold text-slate-950 shadow transition hover:bg-emerald-400">
                            🧾 Confirm Completed &amp; Send to Cashier Billing
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div x-show="payload.orders.length === 0" class="mt-8 rounded-3xl border border-slate-800 bg-slate-900 p-12 text-center">
        <p class="text-base font-bold text-slate-300">No active table orders right now.</p>
        <p class="mt-1 text-xs text-slate-500">Click "+ Create Assisted / Walk-In Order" above or wait for guest QR orders.</p>
    </div>

    <!-- Modal 1: Create New Walk-In / Assisted Table Order (For Customers Without QR Phone) -->
    <div x-show="walkInModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-sm">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-400">Assisted Waitstaff Ordering</span>
                    <h3 class="text-lg font-extrabold text-white">Create New Table Order (Walk-In / Non-QR Guest)</h3>
                </div>
                <button type="button" @click="walkInModalOpen = false" class="rounded-lg bg-slate-800 px-2.5 py-1 text-xs font-bold text-slate-300 hover:text-white">
                    Close &times;
                </button>
            </div>

            <form method="POST" action="{{ route('staff.orders.create') }}" class="mt-4 space-y-4"
                  data-confirm-type="success"
                  data-confirm-badge="Assisted Walk-In Order"
                  data-confirm-title="Create & Confirm This Table Order?"
                  data-confirm-message="This will immediately create a confirmed table order, deduct stock, and dispatch the items to the Kitchen and Bar stations."
                  data-confirm-button="Yes, Create & Route Order">
                @csrf
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Select Table *</label>
                        <select name="restaurant_table_id" x-model="walkInTableId" required
                                class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-xs font-bold text-white">
                            @foreach ($tables as $tbl)
                                <option value="{{ $tbl->id }}">
                                    Table {{ $tbl->table_number }} ({{ strtoupper($tbl->status) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Customer Name *</label>
                        <input type="text" name="customer_name" x-model="walkInName" required maxlength="100"
                               placeholder="e.g., Walk-In Guest"
                               class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white">
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Customer Mobile *</label>
                            <button type="button"
                                    @click="walkInPhone = '0770000000'; if (!walkInName) walkInName = 'Walk-In Guest'"
                                    class="text-[10px] font-bold text-amber-400 hover:underline">
                                No Phone? Use Default
                            </button>
                        </div>
                        <input type="tel" name="customer_phone" x-model="walkInPhone" required maxlength="20"
                               placeholder="0771234567"
                               class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white">
                    </div>
                </div>

                <!-- Menu Item Selector -->
                <div class="max-h-72 space-y-3 overflow-y-auto rounded-2xl border border-slate-800 bg-slate-950 p-3.5">
                    @foreach ($categories as $category)
                        <div class="pt-1 text-xs font-extrabold uppercase tracking-wider text-amber-400">
                            {{ $category->name }} ({{ strtoupper($category->station) }})
                        </div>
                        @foreach ($category->menuItems as $item)
                            <div class="rounded-xl border border-slate-800 bg-slate-900 p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <div class="text-xs font-bold text-white">{{ $item->name }}</div>
                                        <div class="text-[11px] text-slate-400">LKR {{ number_format((float) $item->price, 2) }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="decWalkIn({{ $item->id }})" class="h-7 w-7 rounded-lg bg-slate-800 text-xs font-bold text-white">&minus;</button>
                                        <span class="w-5 text-center text-xs font-bold text-white" x-text="walkInQty({{ $item->id }})"></span>
                                        <button type="button" @click="incWalkIn({ id: {{ $item->id }}, name: @js($item->name), price: {{ (float) $item->price }} })" class="h-7 w-7 rounded-lg bg-amber-500 text-xs font-bold text-slate-950">+</button>
                                    </div>
                                </div>
                                <div x-show="walkInQty({{ $item->id }}) > 0" x-cloak class="mt-2">
                                    <input type="text"
                                           :value="walkInNotes({{ $item->id }})"
                                           @input="setWalkInNotes({{ $item->id }}, $event.target.value)"
                                           placeholder="Kitchen/Bar note (e.g., less spicy, no sugar)..."
                                           class="w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-1 text-[11px] text-white">
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>

                <template x-for="(item, idx) in walkInItemsArray" :key="item.id">
                    <div>
                        <input type="hidden" :name="`items[${idx}][menu_item_id]`" :value="item.id">
                        <input type="hidden" :name="`items[${idx}][quantity]`" :value="item.quantity">
                        <input type="hidden" :name="`items[${idx}][notes]`" :value="item.notes">
                    </div>
                </template>

                <div class="flex items-center justify-between border-t border-slate-800 pt-3">
                    <div class="text-xs text-slate-300">
                        Selected Items: <span class="font-extrabold text-white" x-text="walkInItemsArray.reduce((s, i) => s + i.quantity, 0)"></span>
                        &bull; Subtotal: <span class="font-extrabold text-amber-400">LKR <span x-text="walkInItemsArray.reduce((s, i) => s + (i.price * i.quantity), 0).toFixed(2)"></span></span>
                    </div>

                    <button type="submit"
                            :disabled="walkInItemsArray.length === 0 || !walkInName || !walkInPhone"
                            class="rounded-2xl bg-amber-500 px-6 py-3 text-xs font-extrabold text-slate-950 shadow-lg transition hover:bg-amber-400 disabled:opacity-40">
                        ✓ Create &amp; Confirm Table Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Staff Verbal Add-On Modal (For Existing Active Orders) -->
    <div x-show="addonModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-extrabold text-white">
                    Append Verbal Add-On &bull; Table <span x-text="selectedOrder ? selectedOrder.table_number : ''"></span>
                </h3>
                <button type="button" @click="addonModalOpen = false" class="text-xs text-slate-400 hover:text-white">Close &times;</button>
            </div>

            <form method="POST" :action="selectedOrder ? `/staff/orders/${selectedOrder.id}/append` : '#'" class="mt-4 space-y-4"
                  data-confirm-type="primary"
                  data-confirm-badge="Verbal Add-On Batch"
                  data-confirm-title="Append These Items to the Table Bill?"
                  data-confirm-message="The selected items will be appended as a new confirmed batch on this table's invoice and routed to the Kitchen / Bar."
                  data-confirm-button="Yes, Append Batch">
                @csrf
                <div class="max-h-72 space-y-2.5 overflow-y-auto pr-1">
                    @foreach ($categories as $category)
                        <div class="pt-2 text-xs font-bold uppercase tracking-wider text-amber-400">{{ $category->name }} ({{ strtoupper($category->station) }})</div>
                        @foreach ($category->menuItems as $item)
                            <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                                <div class="flex items-center justify-between">
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
                                <div x-show="addonQty({{ $item->id }}) > 0" x-cloak class="mt-2">
                                    <input type="text"
                                           :value="addonNotes({{ $item->id }})"
                                           @input="setAddonNotes({{ $item->id }}, $event.target.value)"
                                           placeholder="Kitchen/Bar note..."
                                           class="w-full rounded-lg border border-slate-700 bg-slate-900 px-2.5 py-1 text-[11px] text-white">
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>

                <template x-for="(item, idx) in addonItemsArray" :key="item.id">
                    <div>
                        <input type="hidden" :name="`items[${idx}][menu_item_id]`" :value="item.id">
                        <input type="hidden" :name="`items[${idx}][quantity]`" :value="item.quantity">
                        <input type="hidden" :name="`items[${idx}][notes]`" :value="item.notes">
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

        walkInModalOpen: false,
        walkInTableId: config.firstTableId,
        walkInName: '',
        walkInPhone: '',
        walkInCart: {},

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

        confirmOrder(order) {
            window.hotelConfirm({
                type: 'primary',
                badge: 'Kitchen & Bar Dispatch',
                title: `Confirm Order #${order.id} for Table ${order.table_number}?`,
                message: 'Confirming this order will immediately route all pending batches to the Hot Kitchen KDS and Bar stations for preparation.',
                details: [
                    { label: 'Table Number', value: `Table ${order.table_number}` },
                    { label: 'Guest Name', value: order.customer_name },
                    { label: 'Current Bill Total', value: `LKR ${order.grand_total}` }
                ],
                confirmText: 'Yes, Confirm & Route to Kitchen',
                onConfirm: () => {
                    fetch(`/staff/orders/${order.id}/confirm`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(async res => {
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(data.message || 'Unable to confirm order.');
                        if (data.payload) this.payload = data.payload;
                        window.hotelNotify({
                            type: 'success',
                            title: `Order #${order.id} Confirmed`,
                            message: data.message || `Table ${order.table_number} order has been routed to the Kitchen & Bar.`
                        });
                    })
                    .catch(err => {
                        window.hotelNotify({
                            type: 'error',
                            title: 'Confirmation Failed',
                            message: err.message || 'Could not confirm the order. Please try again.'
                        });
                    });
                }
            });
        },

        deliverBatch(order, batch) {
            window.hotelConfirm({
                type: 'success',
                badge: 'Table Delivery Verification',
                title: `Mark Batch #${batch.batch_number} Delivered to Table ${order.table_number}?`,
                message: 'Please confirm that all items in this batch have been physically served to the guest table.',
                details: [
                    { label: 'Table & Order', value: `Table ${order.table_number} (#${order.id})` },
                    { label: 'Batch Number', value: `Batch #${batch.batch_number}` },
                    { label: 'Items in Batch', value: `${batch.all_items ? batch.all_items.length : 0} item(s)` }
                ],
                confirmText: 'Yes, Mark Batch Delivered',
                onConfirm: () => {
                    fetch(`/staff/batches/${batch.id}/deliver`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(async res => {
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(data.message || 'Unable to mark batch delivered.');
                        if (data.payload) this.payload = data.payload;
                        window.hotelNotify({
                            type: 'success',
                            title: `Batch #${batch.batch_number} Delivered`,
                            message: data.message || `Batch #${batch.batch_number} for Table ${order.table_number} marked as served.`
                        });
                    })
                    .catch(err => {
                        window.hotelNotify({
                            type: 'error',
                            title: 'Delivery Update Failed',
                            message: err.message || 'Could not update batch status.'
                        });
                    });
                }
            });
        },

        sendToCashier(order) {
            window.hotelConfirm({
                type: 'success',
                badge: 'Final Billing Handoff',
                title: `Confirm Order #${order.id} Completed & Send to Cashier?`,
                message: 'This will mark all remaining items for this table as served and flag the invoice as "Ready to Bill" on the Cashier Settlement Terminal.',
                details: [
                    { label: 'Table Number', value: `Table ${order.table_number}` },
                    { label: 'Guest Name', value: order.customer_name },
                    { label: 'Running Bill Total', value: `LKR ${order.grand_total}` }
                ],
                confirmText: '🧾 Yes, Send to Cashier Billing',
                onConfirm: () => {
                    fetch(`/staff/orders/${order.id}/send-to-cashier`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(async res => {
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(data.message || 'Unable to send order to cashier.');
                        if (data.payload) this.payload = data.payload;
                        window.hotelNotify({
                            type: 'success',
                            title: 'Sent to Cashier Billing',
                            message: data.message || `Order #${order.id} (Table ${order.table_number}) is now flagged as Ready to Bill for the Cashier.`
                        });
                    })
                    .catch(err => {
                        window.hotelNotify({
                            type: 'error',
                            title: 'Handoff Failed',
                            message: err.message || 'Could not send order to Cashier.'
                        });
                    });
                }
            });
        },

        cancelItem(order, item) {
            window.hotelConfirm({
                type: 'danger',
                badge: 'Cancel Item & Restore Stock',
                title: `Cancel "${item.quantity}x ${item.name}"?`,
                message: 'This item will be removed from the active bill, inventory stock will be restored automatically, and the cancellation will be recorded in the security audit log.',
                details: [
                    { label: 'Table & Order', value: `Table ${order.table_number} (#${order.id})` },
                    { label: 'Item to Cancel', value: `${item.quantity}x ${item.name}` },
                    { label: 'Station', value: (item.station || 'kitchen').toUpperCase() }
                ],
                confirmText: 'Yes, Cancel Item',
                onConfirm: () => {
                    fetch(`/staff/items/${item.id}/cancel`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(async res => {
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(data.message || 'Unable to cancel item.');
                        if (data.payload) this.payload = data.payload;
                        window.hotelNotify({
                            type: 'warning',
                            title: 'Item Cancelled & Stock Restored',
                            message: data.message || `${item.quantity}x ${item.name} was cancelled and the bill total was recalculated.`
                        });
                    })
                    .catch(err => {
                        window.hotelNotify({
                            type: 'error',
                            title: 'Cancellation Failed',
                            message: err.message || 'Could not cancel the item.'
                        });
                    });
                }
            });
        },

        openWalkInModal() {
            this.walkInName = '';
            this.walkInPhone = '';
            this.walkInCart = {};
            this.walkInModalOpen = true;
        },

        get walkInItemsArray() {
            return Object.values(this.walkInCart);
        },

        walkInQty(id) {
            return this.walkInCart[id] ? this.walkInCart[id].quantity : 0;
        },

        walkInNotes(id) {
            return this.walkInCart[id] ? this.walkInCart[id].notes : '';
        },

        setWalkInNotes(id, val) {
            if (this.walkInCart[id]) this.walkInCart[id].notes = val;
        },

        incWalkIn(item) {
            if (!this.walkInCart[item.id]) {
                this.walkInCart[item.id] = { id: item.id, name: item.name, price: Number(item.price), quantity: 1, notes: '' };
            } else {
                this.walkInCart[item.id].quantity++;
            }
        },

        decWalkIn(id) {
            if (!this.walkInCart[id]) return;
            if (this.walkInCart[id].quantity <= 1) {
                delete this.walkInCart[id];
            } else {
                this.walkInCart[id].quantity--;
            }
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

        addonNotes(id) {
            return this.staffAddons[id] ? this.staffAddons[id].notes : '';
        },

        setAddonNotes(id, val) {
            if (this.staffAddons[id]) this.staffAddons[id].notes = val;
        },

        incAddon(item) {
            if (!this.staffAddons[item.id]) {
                this.staffAddons[item.id] = { id: item.id, name: item.name, quantity: 1, notes: '' };
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
