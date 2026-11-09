@extends('layouts.app')

@section('title', 'Management & Administrative Console — ' . config('hotel.name'))

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6" x-data="{ tab: 'reports', editItem: null }">

    <!-- Top Header & Navigation Tabs -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-amber-400">
                Module E &bull; Management &amp; Security Console ({{ $viewer?->hasRole('admin') ? 'Full Admin Access — Unmasked PII' : 'Manager View — Phone PII Masked' }})
            </span>
            <h1 class="mt-1 text-2xl font-extrabold text-white">Shift Ledger, Menu CRUD, Signed QR Tables &amp; Audit Trails</h1>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" @click="tab = 'reports'"
                    :class="tab === 'reports' ? 'bg-amber-500 text-slate-950 font-extrabold' : 'bg-slate-800 text-slate-300'"
                    class="rounded-xl px-4 py-2 text-xs transition">
                📊 Shift &amp; SKU Reports
            </button>
            <button type="button" @click="tab = 'menu'"
                    :class="tab === 'menu' ? 'bg-amber-500 text-slate-950 font-extrabold' : 'bg-slate-800 text-slate-300'"
                    class="rounded-xl px-4 py-2 text-xs transition">
                🍽️ Menu &amp; Stock CRUD
            </button>
            <button type="button" @click="tab = 'tables'"
                    :class="tab === 'tables' ? 'bg-amber-500 text-slate-950 font-extrabold' : 'bg-slate-800 text-slate-300'"
                    class="rounded-xl px-4 py-2 text-xs transition">
                📱 Table &amp; QR Generator
            </button>
            <button type="button" @click="tab = 'audit'"
                    :class="tab === 'audit' ? 'bg-amber-500 text-slate-950 font-extrabold' : 'bg-slate-800 text-slate-300'"
                    class="rounded-xl px-4 py-2 text-xs transition">
                🛡️ Security Audit Logs
            </button>
        </div>
    </div>

    <!-- TAB 1: Shift & Inventory Consumption Reports -->
    <div x-show="tab === 'reports'" class="mt-6 space-y-6">
        <!-- Date Range & Shift Filter Bar -->
        <form method="GET" action="{{ route('admin.reports') }}" class="flex flex-wrap items-end gap-4 rounded-3xl border border-slate-800 bg-slate-900 p-5">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Start Date</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] }}"
                       class="mt-1 rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2 text-xs text-white">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">End Date</label>
                <input type="date" name="end_date" value="{{ $filters['end_date'] }}"
                       class="mt-1 rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2 text-xs text-white">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Shift Filter</label>
                <select name="shift" class="mt-1 rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2 text-xs text-white">
                    <option value="all" @selected($filters['shift'] === 'all')>All Shifts (24h)</option>
                    <option value="morning" @selected($filters['shift'] === 'morning')>Morning Shift (06:00 - 14:00)</option>
                    <option value="afternoon" @selected($filters['shift'] === 'afternoon')>Afternoon / Dinner (14:00 - 22:00)</option>
                    <option value="night" @selected($filters['shift'] === 'night')>Night Shift (22:00 - 06:00)</option>
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-amber-500 px-5 py-2 text-xs font-extrabold text-slate-950 hover:bg-amber-400">
                Apply Filter
            </button>
        </form>

        <!-- Financial Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                <div class="text-[11px] font-bold uppercase text-slate-400">Gross Sales</div>
                <div class="mt-1 text-lg font-extrabold text-white">LKR {{ number_format($financialSummary['gross_sales'], 2) }}</div>
                <div class="text-[11px] text-slate-500">{{ $financialSummary['settled_orders_count'] }} settled bills</div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                <div class="text-[11px] font-bold uppercase text-rose-400">Applied Discounts</div>
                <div class="mt-1 text-lg font-extrabold text-rose-300">&minus; LKR {{ number_format($financialSummary['applied_discounts'], 2) }}</div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                <div class="text-[11px] font-bold uppercase text-emerald-400">Net Revenue</div>
                <div class="mt-1 text-lg font-extrabold text-emerald-300">LKR {{ number_format($financialSummary['net_revenue'], 2) }}</div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                <div class="text-[11px] font-bold uppercase text-sky-400">Collected VAT</div>
                <div class="mt-1 text-lg font-extrabold text-sky-300">LKR {{ number_format($financialSummary['collected_vat'], 2) }}</div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                <div class="text-[11px] font-bold uppercase text-purple-400">Service Charge</div>
                <div class="mt-1 text-lg font-extrabold text-purple-300">LKR {{ number_format($financialSummary['collected_service_charge'], 2) }}</div>
            </div>

            <div class="rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4">
                <div class="text-[11px] font-bold uppercase text-amber-300">Grand Total Collected</div>
                <div class="mt-1 text-lg font-extrabold text-amber-400">LKR {{ number_format($financialSummary['grand_total_collected'], 2) }}</div>
            </div>
        </div>

        <!-- Aggregated SKU Usage Count & Revenue Table -->
        <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
            <h2 class="text-base font-extrabold text-white">Aggregated SKU Consumption &amp; Revenue Breakdown</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-800 text-[11px] uppercase text-slate-400">
                        <tr>
                            <th class="pb-3">SKU / Menu Item</th>
                            <th class="pb-3">Category &amp; Station</th>
                            <th class="pb-3 text-right">Units Sold</th>
                            <th class="pb-3 text-right">Gross Revenue</th>
                            <th class="pb-3 text-right">COGS</th>
                            <th class="pb-3 text-right">Gross Margin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse ($skuConsumption as $sku)
                            <tr>
                                <td class="py-3 font-bold text-white">{{ $sku['sku_name'] }}</td>
                                <td class="py-3 text-slate-400">{{ $sku['category_name'] }} ({{ strtoupper($sku['station']) }})</td>
                                <td class="py-3 text-right font-mono font-bold text-amber-400">{{ $sku['units_sold'] }}</td>
                                <td class="py-3 text-right font-mono text-white">LKR {{ number_format($sku['revenue'], 2) }}</td>
                                <td class="py-3 text-right font-mono text-slate-400">LKR {{ number_format($sku['cogs'], 2) }}</td>
                                <td class="py-3 text-right font-mono font-bold text-emerald-400">LKR {{ number_format($sku['gross_profit'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-500">No settled SKU sales in the selected shift/date window.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Settled Orders Ledger (with Role-Aware Phone Masking) -->
        <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
            <h2 class="text-base font-extrabold text-white">Settled Orders Financial Ledger</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-800 text-[11px] uppercase text-slate-400">
                        <tr>
                            <th class="pb-3">Order ID</th>
                            <th class="pb-3">Table</th>
                            <th class="pb-3">Customer</th>
                            <th class="pb-3">Customer Phone</th>
                            <th class="pb-3">Payment</th>
                            <th class="pb-3 text-right">Subtotal</th>
                            <th class="pb-3 text-right">Discount</th>
                            <th class="pb-3 text-right">VAT + Svc</th>
                            <th class="pb-3 text-right">Grand Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse ($settledOrders as $ord)
                            <tr>
                                <td class="py-3 font-mono font-bold text-white">#{{ $ord->id }}</td>
                                <td class="py-3 text-slate-300">{{ $ord->restaurantTable?->table_number }}</td>
                                <td class="py-3 text-white">{{ $ord->customer_name }}</td>
                                <td class="py-3 font-mono text-amber-300">{{ $ord->displayPhoneFor($viewer) }}</td>
                                <td class="py-3 uppercase text-slate-300">{{ $ord->payment_method }}</td>
                                <td class="py-3 text-right font-mono">LKR {{ number_format((float) $ord->gross_subtotal, 2) }}</td>
                                <td class="py-3 text-right font-mono text-rose-400">LKR {{ number_format((float) $ord->discount_amount, 2) }}</td>
                                <td class="py-3 text-right font-mono text-slate-400">LKR {{ number_format((float) $ord->vat_amount + (float) $ord->service_charge_amount, 2) }}</td>
                                <td class="py-3 text-right font-mono font-bold text-emerald-400">LKR {{ number_format((float) $ord->grand_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-6 text-center text-slate-500">No settled orders found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: Menu Management (Categories & Menu Items CRUD) -->
    <div x-show="tab === 'menu'" x-cloak class="mt-6 grid gap-6 lg:grid-cols-12">
        <!-- Left: Create Category & Create Menu Item Forms -->
        <div class="space-y-6 lg:col-span-4">
            <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-amber-400">Add Department Category</h2>
                <form method="POST" action="{{ route('admin.categories.store') }}" class="mt-4 space-y-3 text-xs">
                    @csrf
                    <div>
                        <label class="block text-slate-400">Category Name</label>
                        <input type="text" name="name" required placeholder="e.g., Signature Cocktails"
                               class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-slate-400">Station Routing</label>
                            <select name="station" class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white">
                                <option value="kitchen">Kitchen</option>
                                <option value="bar">Bar / Service</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-400">Display Order</label>
                            <input type="number" name="display_order" value="1" min="0"
                                   class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white">
                        </div>
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-amber-500 py-2.5 font-extrabold text-slate-950 hover:bg-amber-400">
                        + Create Category
                    </button>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-amber-400">Add New Menu SKU</h2>
                <form method="POST" action="{{ route('admin.menu-items.store') }}" class="mt-4 space-y-3 text-xs">
                    @csrf
                    <div>
                        <label class="block text-slate-400">Department Category</label>
                        <select name="category_id" required class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }} ({{ strtoupper($cat->station) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400">Item Name</label>
                        <input type="text" name="name" required placeholder="e.g., Jaffna Crab Curry"
                               class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white">
                    </div>
                    <div>
                        <label class="block text-slate-400">Description</label>
                        <textarea name="description" rows="2" class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"></textarea>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-slate-400">Selling Price</label>
                            <input type="number" step="0.01" name="price" required value="1500.00"
                                   class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white">
                        </div>
                        <div>
                            <label class="block text-slate-400">COGS Cost</label>
                            <input type="number" step="0.01" name="cost_price" required value="550.00"
                                   class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white">
                        </div>
                        <div>
                            <label class="block text-slate-400">Stock (-1=Unl)</label>
                            <input type="number" name="current_stock" required value="-1" min="-1"
                                   class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white">
                        </div>
                    </div>
                    <div class="flex items-center gap-4 pt-1">
                        <label class="inline-flex items-center gap-2 text-slate-300">
                            <input type="checkbox" name="is_available" value="1" checked class="rounded border-slate-700">
                            Available
                        </label>
                        <label class="inline-flex items-center gap-2 text-slate-300">
                            <input type="checkbox" name="is_vat_exempt" value="1" class="rounded border-slate-700">
                            VAT Exempt
                        </label>
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-amber-500 py-2.5 font-extrabold text-slate-950 hover:bg-amber-400">
                        + Save Menu Item
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Live Menu Items List with Out-of-Stock Switch & Price Update Audit Trigger -->
        <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6 lg:col-span-8">
            <h2 class="text-base font-extrabold text-white">Menu Items &amp; Live Stock Control ({{ $menuItems->count() }} SKUs)</h2>
            <p class="text-xs text-slate-400">Price or COGS updates are automatically recorded in the polymorphic security audit log.</p>

            <div class="mt-4 space-y-3">
                @foreach ($menuItems as $skuItem)
                    <div class="rounded-2xl border border-slate-800 bg-slate-950 p-4">
                        <form method="POST" action="{{ route('admin.menu-items.update', ['menuItem' => $skuItem->id]) }}" class="grid items-center gap-3 sm:grid-cols-12">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="category_id" value="{{ $skuItem->category_id }}">
                            <input type="hidden" name="description" value="{{ $skuItem->description }}">

                            <div class="sm:col-span-4">
                                <input type="text" name="name" value="{{ $skuItem->name }}"
                                       class="w-full rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1.5 text-xs font-bold text-white">
                                <div class="mt-1 text-[10px] text-slate-400">
                                    {{ $skuItem->category?->name }} &bull; Station: {{ strtoupper($skuItem->category?->station ?? 'kitchen') }}
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-[10px] text-slate-400">Price (LKR)</label>
                                <input type="number" step="0.01" name="price" value="{{ $skuItem->price }}"
                                       class="w-full rounded-lg border border-slate-800 bg-slate-900 px-2 py-1 text-xs font-mono text-amber-300">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-[10px] text-slate-400">Cost (COGS)</label>
                                <input type="number" step="0.01" name="cost_price" value="{{ $skuItem->cost_price }}"
                                       class="w-full rounded-lg border border-slate-800 bg-slate-900 px-2 py-1 text-xs font-mono text-slate-300">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-[10px] text-slate-400">Stock (-1=Unl)</label>
                                <input type="number" name="current_stock" value="{{ $skuItem->current_stock }}" min="-1"
                                       class="w-full rounded-lg border border-slate-800 bg-slate-900 px-2 py-1 text-xs font-mono text-white">
                            </div>

                            <div class="flex flex-col items-end gap-1.5 sm:col-span-2">
                                <div class="flex items-center gap-2 text-[10px]">
                                    <label class="inline-flex items-center gap-1 text-slate-300">
                                        <input type="checkbox" name="is_available" value="1" @checked($skuItem->is_available)> Avail
                                    </label>
                                    <label class="inline-flex items-center gap-1 text-emerald-300">
                                        <input type="checkbox" name="is_vat_exempt" value="1" @checked($skuItem->is_vat_exempt)> NoVAT
                                    </label>
                                </div>
                                <button type="submit" class="w-full rounded-lg bg-slate-800 px-3 py-1 text-xs font-bold text-amber-300 hover:bg-amber-500 hover:text-slate-950">
                                    Update SKU
                                </button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- TAB 3: Table & Signed QR Code Generator -->
    <div x-show="tab === 'tables'" x-cloak class="mt-6 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-slate-800 bg-slate-900 p-6">
            <div>
                <h2 class="text-base font-extrabold text-white">Tamper-Proof Signed Table QR Generator</h2>
                <p class="text-xs text-slate-400">Each QR encodes <code class="text-amber-300">URL::signedRoute('table.entry', ['qr_token' => $table->qr_token])</code>.</p>
            </div>

            <form method="POST" action="{{ route('admin.tables.store') }}" class="flex items-center gap-2">
                @csrf
                <input type="text" name="table_number" required placeholder="e.g., T-09 or VIP-02"
                       class="rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2 text-xs text-white">
                <button type="submit" class="rounded-xl bg-amber-500 px-4 py-2 text-xs font-extrabold text-slate-950 hover:bg-amber-400">
                    + Add Table
                </button>
            </form>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($tables as $tbl)
                @php
                    $signedUrl = $tbl->signedQrUrl();
                    $scannableUrl = $tbl->scannableQrUrl();
                @endphp
                <div class="flex flex-col justify-between rounded-3xl border border-slate-800 bg-slate-900 p-5 text-center shadow-xl">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-extrabold text-white">Table {{ $tbl->table_number }}</span>
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase {{ $tbl->status === 'vacant' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300' }}">
                                {{ $tbl->status }}
                            </span>
                        </div>

                        <!-- Printable SVG QR Box -->
                        <div class="mx-auto my-4 flex h-44 w-44 items-center justify-center rounded-2xl bg-white p-3 shadow-inner">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ urlencode($scannableUrl) }}"
                                 alt="QR Table {{ $tbl->table_number }}"
                                 class="h-36 w-36 object-contain">
                        </div>

                        <p class="truncate font-mono text-[10px] text-slate-400" title="{{ $tbl->qr_token }}">
                            UUID: {{ $tbl->qr_token }}
                        </p>
                    </div>

                    <div class="mt-4 space-y-2">
                        <a href="{{ $signedUrl }}" target="_blank"
                           class="block w-full rounded-xl bg-amber-500 py-2 text-xs font-extrabold text-slate-950 hover:bg-amber-400">
                            Open Signed QR Link &nearr;
                        </a>

                        <form method="POST" action="{{ route('admin.tables.regenerate-qr', ['table' => $tbl->id]) }}">
                            @csrf
                            <button type="submit" class="w-full rounded-xl border border-slate-700 bg-slate-950 py-1.5 text-[11px] font-semibold text-slate-300 hover:border-rose-500/50 hover:text-rose-300">
                                Rotate QR Token UUID
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- TAB 4: Security Audit Logs & JSON Diffs -->
    <div x-show="tab === 'audit'" x-cloak class="mt-6 rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
        <h2 class="text-base font-extrabold text-white">Security &amp; Financial Audit Trail</h2>
        <p class="text-xs text-slate-400">Tracks discount overrides (&gt;10% Manager PIN), item cancellations, and menu price updates with JSON diffs.</p>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-slate-800 text-[11px] uppercase text-slate-400">
                    <tr>
                        <th class="pb-3">Timestamp</th>
                        <th class="pb-3">Actor</th>
                        <th class="pb-3">Event Type</th>
                        <th class="pb-3">Target Entity</th>
                        <th class="pb-3">IP Address</th>
                        <th class="pb-3">Old Values (JSON)</th>
                        <th class="pb-3">New Values (JSON)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse ($auditLogs as $log)
                        <tr>
                            <td class="py-3 font-mono text-slate-400">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="py-3 font-bold text-white">
                                {{ $log->user?->name ?? 'System' }}
                                @if ($log->user?->role)
                                    <span class="ml-1 rounded bg-slate-800 px-1.5 py-0.5 text-[10px] uppercase text-amber-300">{{ $log->user->role->name }}</span>
                                @endif
                            </td>
                            <td class="py-3">
                                <span class="rounded-full bg-amber-500/15 px-2.5 py-0.5 font-mono text-[11px] font-bold text-amber-300">
                                    {{ $log->event_type }}
                                </span>
                            </td>
                            <td class="py-3 font-mono text-slate-300">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                            <td class="py-3 font-mono text-slate-400">{{ $log->ip_address }}</td>
                            <td class="py-3 font-mono text-[11px] text-rose-300">{{ json_encode($log->old_values) }}</td>
                            <td class="py-3 font-mono text-[11px] text-emerald-300">{{ json_encode($log->new_values) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-500">No audit events recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

