@extends('layouts.app')

@section('title', 'Kitchen Display System (KDS) — ' . config('hotel.name'))

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6"
     x-data="kdsStationApp({
         initialPayload: @js($initialPayload),
         stationFilter: @js($stationFilter),
         csrfToken: @js(csrf_token())
     })"
     x-init="startKdsPoller()">

    <!-- KDS Header (Zero Financial Figures Displayed) -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-slate-800 bg-slate-900 p-5 shadow-xl">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Live Kitchen Display System (KDS)</span>
            </div>
            <h1 class="mt-1 text-2xl font-extrabold text-white">Active Preparation Tickets by Table &amp; Batch</h1>
            <p class="text-xs text-slate-400">Strict operational view &bull; Last synced: <span x-text="payload.polled_at"></span></p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    @click="changeStation('all')"
                    :class="station === 'all' ? 'bg-amber-500 text-slate-950 font-extrabold' : 'bg-slate-800 text-slate-300'"
                    class="rounded-xl px-4 py-2 text-xs transition">
                All Stations
            </button>
            <button type="button"
                    @click="changeStation('kitchen')"
                    :class="station === 'kitchen' ? 'bg-amber-500 text-slate-950 font-extrabold' : 'bg-slate-800 text-slate-300'"
                    class="rounded-xl px-4 py-2 text-xs transition">
                Hot Kitchen Only
            </button>
            <button type="button"
                    @click="changeStation('bar')"
                    :class="station === 'bar' ? 'bg-amber-500 text-slate-950 font-extrabold' : 'bg-slate-800 text-slate-300'"
                    class="rounded-xl px-4 py-2 text-xs transition">
                Bar / Beverage Station
            </button>
        </div>
    </div>

    <!-- Live Batch Cards Grid -->
    <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <template x-for="card in payload.cards" :key="card.batch_id">
            <div class="flex flex-col justify-between rounded-3xl border p-5 shadow-xl"
                 :class="{
                     'border-amber-500/50 bg-slate-900': card.batch_status === 'confirmed',
                     'border-purple-500/60 bg-purple-950/15': card.batch_status === 'preparing',
                     'border-emerald-500/60 bg-emerald-950/15': card.batch_status === 'ready'
                 }">

                <div>
                    <!-- Ticket Header: Table Number & Batch Number -->
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="rounded-xl bg-amber-500 px-3 py-1 text-base font-extrabold text-slate-950"
                                  x-text="'TABLE ' + card.table_number"></span>
                            <span class="rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-1 text-xs font-extrabold text-white"
                                  x-text="'BATCH #' + card.batch_number"></span>
                        </div>

                        <div class="text-right">
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-extrabold uppercase"
                                  :class="{
                                      'bg-amber-500/20 text-amber-300': card.batch_status === 'confirmed',
                                      'bg-purple-500/20 text-purple-300': card.batch_status === 'preparing',
                                      'bg-emerald-500/20 text-emerald-300': card.batch_status === 'ready'
                                  }"
                                  x-text="card.batch_status"></span>
                            <div class="mt-0.5 text-[11px] text-slate-400" x-text="card.created_time + ' (' + card.elapsed_human + ')'"></div>
                        </div>
                    </div>

                    <!-- Preparation Line Items & Notes -->
                    <div class="mt-4 divide-y divide-slate-800/70">
                        <template x-for="item in card.items" :key="item.id">
                            <div class="py-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-800 text-sm font-extrabold text-amber-400"
                                              x-text="item.quantity + 'x'"></span>
                                        <div>
                                            <div class="text-sm font-bold text-white" x-text="item.name"></div>
                                            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400"
                                                  x-text="item.station"></span>
                                        </div>
                                    </div>

                                    <!-- Individual Item Cooking -> Ready Toggle -->
                                    <div class="flex items-center gap-1.5">
                                        <button type="button"
                                                x-show="item.item_status === 'pending'"
                                                @click="toggleItem(item.id, 'cooking')"
                                                class="rounded-lg bg-purple-500/20 border border-purple-500/40 px-2.5 py-1 text-xs font-bold text-purple-200 hover:bg-purple-500 hover:text-slate-950">
                                            Mark Cooking
                                        </button>

                                        <button type="button"
                                                x-show="item.item_status === 'cooking'"
                                                @click="toggleItem(item.id, 'ready')"
                                                class="rounded-lg bg-emerald-500 px-2.5 py-1 text-xs font-extrabold text-slate-950 hover:bg-emerald-400">
                                            Mark Ready
                                        </button>

                                        <span x-show="item.item_status === 'ready'"
                                              class="rounded-lg bg-emerald-500/20 px-2.5 py-1 text-xs font-extrabold text-emerald-300">
                                            ✓ READY
                                        </span>
                                    </div>
                                </div>

                                <!-- Prominent Preparation Notes -->
                                <div x-show="item.notes"
                                     class="mt-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-semibold text-amber-200">
                                    Chef Note: <span x-text="item.notes"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Batch-Level Toggle Buttons: Mark Cooking -> Mark Ready -->
                <div class="mt-4 grid grid-cols-2 gap-2 border-t border-slate-800 pt-4">
                    <button type="button"
                            @click="toggleBatch(card.batch_id, 'preparing')"
                            :disabled="card.batch_status === 'preparing' || card.batch_status === 'ready'"
                            class="rounded-xl border border-purple-500/40 bg-purple-500/20 py-2.5 text-xs font-extrabold text-purple-200 transition hover:bg-purple-500 hover:text-slate-950 disabled:opacity-40">
                        🔥 Mark Cooking
                    </button>

                    <button type="button"
                            @click="toggleBatch(card.batch_id, 'ready')"
                            :disabled="card.batch_status === 'ready'"
                            class="rounded-xl bg-emerald-500 py-2.5 text-xs font-extrabold text-slate-950 shadow transition hover:bg-emerald-400 disabled:opacity-40">
                        ✓ Mark Ready
                    </button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="payload.cards.length === 0" class="mt-8 rounded-3xl border border-slate-800 bg-slate-900 p-12 text-center">
        <p class="text-base font-bold text-slate-300">All confirmed batches are cleared!</p>
        <p class="mt-1 text-xs text-slate-500">Waiting for Sales Assistants to confirm incoming table orders.</p>
    </div>
</div>

@push('scripts')
<script>
function kdsStationApp(config) {
    return {
        payload: config.initialPayload,
        station: config.stationFilter || 'all',

        startKdsPoller() {
            setInterval(() => {
                this.fetchKds();
            }, 3000);
        },

        changeStation(newStation) {
            this.station = newStation;
            this.fetchKds();
        },

        fetchKds() {
            fetch(`/kitchen/display?poll=1&station=${encodeURIComponent(this.station)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.ok ? res.json() : null)
            .then(data => {
                if (data) this.payload = data;
            })
            .catch(() => {});
        },

        toggleBatch(batchId, status) {
            fetch(`/kitchen/batches/${batchId}/status?station=${encodeURIComponent(this.station)}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ status })
            })
            .then(res => res.json())
            .then(data => {
                if (data.payload) this.payload = data.payload;
            });
        },

        toggleItem(itemId, itemStatus) {
            fetch(`/kitchen/items/${itemId}/status?station=${encodeURIComponent(this.station)}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ item_status: itemStatus })
            })
            .then(res => res.json())
            .then(data => {
                if (data.payload) this.payload = data.payload;
            });
        }
    };
}
</script>
@endpush
@endsection

