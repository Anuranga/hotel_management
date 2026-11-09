@extends('layouts.app')

@section('title', $hotelName . ' — QR Billing & Order Management System')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10" x-data="qrScannerPortal()">
    <div class="rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900 to-slate-950 p-8 shadow-2xl">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-amber-300">
                    Production Hotel QR POS &amp; KDS Suite
                </span>
                <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                    {{ $hotelName }}
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-400">
                    Tamper-proof Signed QR Guest Self-Service, Real-Time Handheld Staff Queue, Kitchen Display System (KDS), Pessimistic-Locked Cashier Settlement with Manager PIN Override, and Shift Financial &amp; Audit Consoles.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button"
                        @click="openScanner()"
                        class="rounded-xl border border-emerald-500/50 bg-emerald-500/15 px-5 py-2.5 text-sm font-bold text-emerald-300 shadow transition hover:bg-emerald-500 hover:text-slate-950">
                    📷 Scan Table QR Code
                </button>
                @auth
                    <a href="{{ route('staff.dashboard') }}" class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-slate-950 shadow transition hover:bg-amber-400">
                        Open Active Terminal
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-slate-950 shadow transition hover:bg-amber-400">
                        Staff / Terminal Login
                    </a>
                @endauth
            </div>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('staff.dashboard') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/60 p-5 transition hover:border-amber-500/50">
                <div class="text-xs font-semibold uppercase tracking-wider text-amber-400">Module B</div>
                <div class="mt-1 text-lg font-bold text-white group-hover:text-amber-300">Sales Assistant Queue</div>
                <p class="mt-1 text-xs text-slate-400">3s live polling, Web Audio chime, 1-tap order confirmation, verbal add-ons, and batch delivery.</p>
            </a>

            <a href="{{ route('kitchen.display') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/60 p-5 transition hover:border-emerald-500/50">
                <div class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Module C</div>
                <div class="mt-1 text-lg font-bold text-white group-hover:text-emerald-300">Kitchen Display (KDS)</div>
                <p class="mt-1 text-xs text-slate-400">Zero-financial chef station grouped by Table &amp; Batch with Mark Cooking &rarr; Mark Ready toggles.</p>
            </a>

            <a href="{{ route('cashier.billing') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/60 p-5 transition hover:border-sky-500/50">
                <div class="text-xs font-semibold uppercase tracking-wider text-sky-400">Module D</div>
                <div class="mt-1 text-lg font-bold text-white group-hover:text-sky-300">Cashier Settlement</div>
                <p class="mt-1 text-xs text-slate-400">Unified batch billing, Manager PIN gate (&gt;10% discount), SMS digital receipt &amp; 80mm thermal print.</p>
            </a>

            <a href="{{ route('admin.reports') }}" class="group rounded-2xl border border-slate-800 bg-slate-900/60 p-5 transition hover:border-purple-500/50">
                <div class="text-xs font-semibold uppercase tracking-wider text-purple-400">Module E</div>
                <div class="mt-1 text-lg font-bold text-white group-hover:text-purple-300">Admin &amp; Shift Reports</div>
                <p class="mt-1 text-xs text-slate-400">Menu &amp; stock CRUD, Signed QR generator, Shift SKU consumption, and JSON-diff Security Audit Logs.</p>
            </a>
        </div>
    </div>

    <div class="mt-10">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-white">Module A: Guest Self-Service QR Tables (Signed URLs)</h2>
                <p class="text-xs text-slate-400">Click any table below to simulate scanning its tamper-proof signed QR code on a guest smartphone.</p>
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            @forelse ($tables as $table)
                @php
                    $browserSignedUrl = $table->signedQrUrl();
                    $phoneScannableUrl = $table->scannableQrUrl();
                @endphp
                <div class="flex flex-col justify-between rounded-2xl border border-slate-800 bg-slate-900 p-5 transition hover:border-amber-500/60">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-extrabold text-white">Table {{ $table->table_number }}</span>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase {{ $table->status === 'vacant' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30' : ($table->status === 'occupied' ? 'bg-amber-500/15 text-amber-300 border border-amber-500/30' : 'bg-sky-500/15 text-sky-300 border border-sky-500/30') }}">
                                {{ $table->status }}
                            </span>
                        </div>

                        <!-- Scannable QR Code for Phone Camera / QR Scanner -->
                        <div class="mx-auto my-3 flex h-36 w-36 items-center justify-center rounded-xl bg-white p-2 shadow-inner">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={{ urlencode($phoneScannableUrl) }}"
                                 alt="Scan QR for Table {{ $table->table_number }}"
                                 class="h-32 w-32 object-contain">
                        </div>

                        <div class="text-center text-xs text-slate-400">
                            <p class="truncate font-mono text-[10px] text-slate-500">UUID: {{ $table->qr_token }}</p>
                            @if ($table->activeOrder)
                                <p class="mt-1 font-medium text-amber-300">Active Order #{{ $table->activeOrder->id }} ({{ str_replace('_', ' ', $table->activeOrder->order_status) }})</p>
                            @else
                                <p class="mt-1 text-emerald-400">Scan with Phone or Click Below</p>
                            @endif
                        </div>
                    </div>

                    <a href="{{ $browserSignedUrl }}"
                       class="mt-3 block w-full rounded-xl bg-amber-500 py-2 text-center text-xs font-extrabold text-slate-950 transition hover:bg-amber-400">
                        Open Menu (Table {{ $table->table_number }}) &rarr;
                    </a>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-slate-800 bg-slate-900 p-6 text-center text-sm text-slate-400">
                    No restaurant tables seeded yet. Run <code class="text-amber-300">php artisan db:seed</code>.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Built-In Camera / Image QR Scanner Modal -->
    <div x-show="scannerOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-sm">
        <div @click.away="closeScanner()"
             class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-extrabold text-white">📷 Scan Table QR Code</h3>
                <button type="button" @click="closeScanner()" class="text-xs text-slate-400 hover:text-white">Close &times;</button>
            </div>
            <p class="mt-2 text-xs text-slate-400">
                Point your camera at a table QR code or upload a QR code image to automatically open that table's digital menu.
            </p>
            <div id="qr-reader" class="mt-4 overflow-hidden rounded-2xl border border-slate-800 bg-slate-950"></div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
function qrScannerPortal() {
    return {
        scannerOpen: false,
        scannerInstance: null,

        openScanner() {
            this.scannerOpen = true;
            this.$nextTick(() => {
                if (typeof Html5QrcodeScanner === 'undefined') return;
                this.scannerInstance = new Html5QrcodeScanner('qr-reader', { fps: 10, qrbox: { width: 220, height: 220 } }, false);
                this.scannerInstance.render((decodedText) => {
                    this.closeScanner();
                    window.location.href = decodedText;
                }, () => {});
            });
        },

        closeScanner() {
            this.scannerOpen = false;
            if (this.scannerInstance) {
                this.scannerInstance.clear().catch(() => {});
                this.scannerInstance = null;
            }
        }
    };
}
</script>
@endpush
@endsection
