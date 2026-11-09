<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          theme: localStorage.getItem('hotel_theme') || '{{ request()->routeIs('kitchen.*') ? 'dark' : 'light' }}',
          toggleTheme() {
              this.theme = this.theme === 'dark' ? 'light' : 'dark';
              localStorage.setItem('hotel_theme', this.theme);
          }
      }"
      :class="theme === 'light' ? 'theme-light' : 'theme-dark'"
      class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('hotel.name', 'Odara Food Pvt Limited'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace !important;
        }

        /* Warm Luxury Hospitality Light Mode Overrides */
        html.theme-light,
        html.theme-light body {
            background-color: #f8f6f2 !important;
            color: #1c1917 !important;
        }
        html.theme-light .bg-slate-950,
        html.theme-light .bg-slate-950\/95,
        html.theme-light .bg-slate-950\/90,
        html.theme-light .bg-slate-950\/80 {
            background-color: #f3efe8 !important;
        }
        html.theme-light .bg-slate-900,
        html.theme-light .bg-slate-900\/95,
        html.theme-light .bg-slate-900\/90,
        html.theme-light .bg-slate-900\/80,
        html.theme-light .bg-slate-900\/60 {
            background-color: #ffffff !important;
            box-shadow: 0 4px 20px -4px rgba(28, 25, 23, 0.06);
        }
        html.theme-light .from-slate-900 {
            --tw-gradient-from: #ffffff !important;
            --tw-gradient-to: #f8f6f2 !important;
            --tw-gradient-stops: var(--tw-gradient-from), #fcfaf7, var(--tw-gradient-to) !important;
        }
        html.theme-light .bg-slate-800 {
            background-color: #eae4dc !important;
        }
        html.theme-light .border-slate-800,
        html.theme-light .border-slate-800\/80,
        html.theme-light .border-slate-800\/60,
        html.theme-light .border-slate-700 {
            border-color: #e4ded5 !important;
        }
        html.theme-light .divide-slate-800\/60 > :not([hidden]) ~ :not([hidden]),
        html.theme-light .divide-slate-800\/70 > :not([hidden]) ~ :not([hidden]) {
            border-color: #e9e4dc !important;
        }
        html.theme-light .text-white,
        html.theme-light .text-slate-100,
        html.theme-light .text-slate-200 {
            color: #1c1917 !important;
        }
        html.theme-light .text-slate-300 {
            color: #44403c !important;
        }
        html.theme-light .text-slate-400,
        html.theme-light .text-slate-500 {
            color: #68625d !important;
        }
        html.theme-light .text-amber-300,
        html.theme-light .text-amber-400 {
            color: #b45309 !important;
        }
        html.theme-light .text-emerald-300,
        html.theme-light .text-emerald-400,
        html.theme-light .text-emerald-200 {
            color: #047857 !important;
        }
        html.theme-light .text-rose-300,
        html.theme-light .text-rose-400,
        html.theme-light .text-rose-200 {
            color: #be123c !important;
        }
        html.theme-light .text-sky-300,
        html.theme-light .text-sky-400 {
            color: #0369a1 !important;
        }
        html.theme-light .text-purple-200,
        html.theme-light .text-purple-300,
        html.theme-light .text-purple-400 {
            color: #6d28d9 !important;
        }
        html.theme-light input,
        html.theme-light select,
        html.theme-light textarea {
            background-color: #fcfaf7 !important;
            color: #1c1917 !important;
            border-color: #d6cfc5 !important;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; color: #000000 !important; }
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-full bg-slate-950 text-slate-100 antialiased selection:bg-amber-500 selection:text-slate-950 transition-colors duration-200">
    @auth
        <header class="no-print sticky top-0 z-40 border-b border-slate-800 bg-slate-900/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div class="flex items-center gap-3">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-semibold tracking-tight text-white">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500 font-bold text-slate-950 shadow-sm">OF</span>
                        <div>
                            <span class="block text-sm font-bold leading-none">{{ config('hotel.name') }}</span>
                            <span class="text-xs text-slate-400">QR Billing &amp; POS Suite</span>
                        </div>
                    </a>
                    <span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-2.5 py-0.5 text-xs font-medium uppercase tracking-wider text-amber-300">
                        {{ auth()->user()->role?->label ?? auth()->user()->role?->name ?? 'Staff' }}
                    </span>
                </div>

                <nav class="flex flex-wrap items-center gap-1.5 text-xs font-medium sm:text-sm">
                    @if (auth()->user()->hasRole(['staff', 'manager', 'admin']))
                        <a href="{{ route('staff.dashboard') }}"
                           class="rounded-lg px-3 py-1.5 transition {{ request()->routeIs('staff.*') ? 'bg-amber-500 text-slate-950 font-semibold' : 'text-slate-300 hover:bg-slate-800' }}">
                            Staff Queue
                        </a>
                    @endif

                    @if (auth()->user()->hasRole(['kitchen', 'manager', 'admin']))
                        <a href="{{ route('kitchen.display') }}"
                           class="rounded-lg px-3 py-1.5 transition {{ request()->routeIs('kitchen.*') ? 'bg-amber-500 text-slate-950 font-semibold' : 'text-slate-300 hover:bg-slate-800' }}">
                            Kitchen KDS
                        </a>
                    @endif

                    @if (auth()->user()->hasRole(['cashier', 'manager', 'admin']))
                        <a href="{{ route('cashier.billing') }}"
                           class="rounded-lg px-3 py-1.5 transition {{ request()->routeIs('cashier.*') ? 'bg-amber-500 text-slate-950 font-semibold' : 'text-slate-300 hover:bg-slate-800' }}">
                            Cashier Billing
                        </a>
                    @endif

                    @if (auth()->user()->hasRole(['manager', 'admin']))
                        <a href="{{ route('admin.reports') }}"
                           class="rounded-lg px-3 py-1.5 transition {{ request()->routeIs('admin.*', 'manager.*') ? 'bg-amber-500 text-slate-950 font-semibold' : 'text-slate-300 hover:bg-slate-800' }}">
                            Admin &amp; Reports
                        </a>
                    @endif

                    <button type="button"
                            @click="toggleTheme()"
                            class="ml-1 rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs font-bold text-slate-200 transition hover:border-amber-500"
                            title="Switch Light / Dark Hospitality Theme">
                        <span x-text="theme === 'light' ? '🌙 Dark' : '☀️ Light'"></span>
                    </button>

                    <form method="POST" action="{{ route('logout') }}" class="ml-1 inline">
                        @csrf
                        <button type="submit" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-slate-300 transition hover:border-rose-500/50 hover:text-rose-300">
                            Sign Out ({{ auth()->user()->name }})
                        </button>
                    </form>
                </nav>
            </div>
        </header>
    @else
        <!-- Floating Theme Switcher for Guest Self-Service & Portal Pages -->
        <div class="no-print fixed right-4 top-3 z-50">
            <button type="button"
                    @click="toggleTheme()"
                    class="rounded-full border border-slate-700 bg-slate-900/90 px-3 py-1.5 text-xs font-bold text-slate-200 shadow-lg backdrop-blur transition hover:border-amber-500">
                <span x-text="theme === 'light' ? '🌙 Dark Mode' : '☀️ Light Mode'"></span>
            </button>
        </div>
    @endauth

    @if (session('status'))
        <div class="no-print mx-auto max-w-7xl px-4 pt-4">
            <div class="flex items-center justify-between rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="no-print mx-auto max-w-7xl px-4 pt-4">
            <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                <p class="font-semibold">Please resolve the following validation errors:</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
