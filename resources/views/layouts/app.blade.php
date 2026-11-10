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

                    <form method="POST" action="{{ route('logout') }}" class="ml-1 inline"
                          data-confirm-title="Sign Out of Terminal?"
                          data-confirm-message="You are about to end your active session on this terminal. Any unsaved modal inputs will be cleared."
                          data-confirm-type="warning"
                          data-confirm-button="Yes, Sign Out">
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

    <!-- Enhanced Server-Side Flash & Validation Alerts -->
    @if (session('status') || session('error') || session('warning') || $errors->any())
        <div class="no-print mx-auto max-w-7xl space-y-3 px-4 pt-4" x-data="{ showStatus: true, showError: true, showValidation: true }">
            @if (session('status'))
                <div x-show="showStatus"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="flex items-start justify-between gap-4 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-4 shadow-lg">
                    <div class="flex items-start gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-sm font-extrabold text-slate-950 shadow">
                            ✓
                        </span>
                        <div>
                            <div class="text-xs font-extrabold uppercase tracking-wider text-emerald-400">Operation Successful</div>
                            <p class="mt-0.5 text-sm font-semibold text-white">{{ session('status') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="showStatus = false" class="rounded-lg px-2 py-1 text-xs font-bold text-slate-400 hover:bg-slate-800 hover:text-white">
                        Dismiss &times;
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div x-show="showError"
                     x-transition
                     class="flex items-start justify-between gap-4 rounded-2xl border border-rose-500/50 bg-rose-500/10 p-4 shadow-lg">
                    <div class="flex items-start gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-rose-500 text-sm font-extrabold text-white shadow">
                            !
                        </span>
                        <div>
                            <div class="text-xs font-extrabold uppercase tracking-wider text-rose-400">Action Blocked / Error</div>
                            <p class="mt-0.5 text-sm font-semibold text-white">{{ session('error') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="showError = false" class="rounded-lg px-2 py-1 text-xs font-bold text-slate-400 hover:bg-slate-800 hover:text-white">
                        Dismiss &times;
                    </button>
                </div>
            @endif

            @if ($errors->any())
                <div x-show="showValidation"
                     x-transition
                     class="rounded-2xl border border-rose-500/50 bg-rose-500/10 p-4 shadow-lg">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-rose-500 text-sm font-extrabold text-white shadow">
                                ⚠
                            </span>
                            <div>
                                <div class="text-xs font-extrabold uppercase tracking-wider text-rose-400">
                                    Please Check Your Input ({{ $errors->count() }} {{ Str::plural('Issue', $errors->count()) }} Found)
                                </div>
                                <p class="mt-0.5 text-xs text-slate-300">
                                    The action could not be completed until the following details are corrected:
                                </p>
                                <ul class="mt-2 list-inside list-disc space-y-1 text-xs font-semibold text-rose-200">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" @click="showValidation = false" class="rounded-lg px-2 py-1 text-xs font-bold text-slate-400 hover:bg-slate-800 hover:text-white">
                            Dismiss &times;
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    <!-- Global Interactive Toast Notification Stack & Confirmation Modal System -->
    <div x-data="hotelUiFeedbackHub()"
         x-init="initHub()"
         class="no-print">

        <!-- Floating Toast Notifications (Top-Right) -->
        <div class="pointer-events-none fixed right-4 top-16 z-50 flex w-full max-w-sm flex-col gap-2.5">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-x-6 scale-95"
                     x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="pointer-events-auto rounded-2xl border p-4 shadow-2xl backdrop-blur-md"
                     :class="{
                         'border-emerald-500/50 bg-slate-900/95': toast.type === 'success',
                         'border-rose-500/60 bg-slate-900/95': toast.type === 'error',
                         'border-amber-500/50 bg-slate-900/95': toast.type === 'warning',
                         'border-sky-500/50 bg-slate-900/95': toast.type === 'info'
                     }">
                    <div class="flex items-start gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-sm font-extrabold shadow"
                              :class="{
                                  'bg-emerald-500 text-slate-950': toast.type === 'success',
                                  'bg-rose-500 text-white': toast.type === 'error',
                                  'bg-amber-500 text-slate-950': toast.type === 'warning',
                                  'bg-sky-500 text-slate-950': toast.type === 'info'
                              }"
                              x-text="toast.type === 'success' ? '✓' : (toast.type === 'error' ? '!' : (toast.type === 'warning' ? '⚠' : 'ℹ'))"></span>

                        <div class="grow">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-extrabold uppercase tracking-wider"
                                      :class="{
                                          'text-emerald-400': toast.type === 'success',
                                          'text-rose-400': toast.type === 'error',
                                          'text-amber-400': toast.type === 'warning',
                                          'text-sky-400': toast.type === 'info'
                                      }"
                                      x-text="toast.title"></span>
                                <button type="button" @click="dismissToast(toast.id)" class="text-xs text-slate-400 hover:text-white">&times;</button>
                            </div>
                            <p class="mt-1 text-xs font-medium leading-relaxed text-white" x-text="toast.message"></p>
                            <template x-if="toast.details && toast.details.length">
                                <ul class="mt-1.5 list-inside list-disc space-y-0.5 text-[11px] text-slate-300">
                                    <template x-for="(line, i) in toast.details" :key="i">
                                        <li x-text="line"></li>
                                    </template>
                                </ul>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Global Safety Confirmation Modal (Prevents Accidental Clicks) -->
        <div x-show="confirmModal.open"
             x-cloak
             @keydown.escape.window="cancelConfirm()"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-sm">
            <div @click.away="cancelConfirm()"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">

                <div class="flex items-start gap-3.5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-lg font-extrabold shadow-lg"
                          :class="{
                              'bg-emerald-500 text-slate-950': confirmModal.type === 'success',
                              'bg-amber-500 text-slate-950': confirmModal.type === 'warning' || confirmModal.type === 'primary',
                              'bg-rose-500 text-white': confirmModal.type === 'danger'
                          }"
                          x-text="confirmModal.type === 'danger' ? '!' : (confirmModal.type === 'success' ? '✓' : '?')"></span>

                    <div class="grow">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider"
                              :class="{
                                  'text-emerald-400': confirmModal.type === 'success',
                                  'text-amber-400': confirmModal.type === 'warning' || confirmModal.type === 'primary',
                                  'text-rose-400': confirmModal.type === 'danger'
                              }"
                              x-text="confirmModal.badge || 'Action Confirmation Required'"></span>
                        <h3 class="mt-0.5 text-base font-extrabold text-white" x-text="confirmModal.title"></h3>
                        <p class="mt-1.5 text-xs leading-relaxed text-slate-300" x-text="confirmModal.message"></p>
                    </div>
                </div>

                <!-- Contextual Summary Details Box -->
                <template x-if="confirmModal.details && confirmModal.details.length > 0">
                    <div class="mt-4 rounded-2xl border border-slate-800 bg-slate-950 p-3.5 text-xs space-y-1.5">
                        <template x-for="(item, idx) in confirmModal.details" :key="idx">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-slate-400" x-text="item.label"></span>
                                <span class="font-mono font-bold text-white" x-text="item.value"></span>
                            </div>
                        </template>
                    </div>
                </template>

                <div class="mt-5 flex items-center justify-end gap-2.5 border-t border-slate-800 pt-4">
                    <button type="button"
                            @click="cancelConfirm()"
                            class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-xs font-bold text-slate-300 transition hover:bg-slate-700 hover:text-white">
                        <span x-text="confirmModal.cancelText || 'No, Cancel'"></span>
                    </button>

                    <button type="button"
                            @click="acceptConfirm()"
                            class="rounded-xl px-5 py-2.5 text-xs font-extrabold shadow-lg transition"
                            :class="{
                                'bg-emerald-500 text-slate-950 hover:bg-emerald-400': confirmModal.type === 'success',
                                'bg-amber-500 text-slate-950 hover:bg-amber-400': confirmModal.type === 'warning' || confirmModal.type === 'primary',
                                'bg-rose-500 text-white hover:bg-rose-400': confirmModal.type === 'danger'
                            }">
                        <span x-text="confirmModal.confirmText || 'Yes, Proceed'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function hotelUiFeedbackHub() {
        return {
            toasts: [],
            confirmModal: {
                open: false,
                type: 'primary',
                badge: 'Please Confirm Action',
                title: '',
                message: '',
                details: [],
                confirmText: 'Yes, Proceed',
                cancelText: 'No, Go Back',
                onConfirm: null,
                onCancel: null
            },

            initHub() {
                window.hotelNotify = (options) => this.pushToast(options);
                window.hotelConfirm = (options) => this.openConfirm(options);

                // Automatically intercept forms or submit buttons with data-confirm-title
                document.addEventListener('submit', (e) => {
                    const form = e.target;
                    if (!(form instanceof HTMLFormElement)) return;
                    if (form.dataset.confirmedBypass === '1') {
                        delete form.dataset.confirmedBypass;
                        return;
                    }

                    const submitter = e.submitter;
                    const title = (submitter && submitter.dataset.confirmTitle) || form.dataset.confirmTitle;
                    if (!title) return;

                    e.preventDefault();
                    const message = (submitter && submitter.dataset.confirmMessage) || form.dataset.confirmMessage || 'Please confirm that you want to proceed with this action.';
                    const type = (submitter && submitter.dataset.confirmType) || form.dataset.confirmType || 'primary';
                    const confirmText = (submitter && submitter.dataset.confirmButton) || form.dataset.confirmButton || 'Yes, Confirm';
                    const badge = (submitter && submitter.dataset.confirmBadge) || form.dataset.confirmBadge || 'Safety Confirmation';

                    let details = [];
                    const rawDetails = (submitter && submitter.dataset.confirmDetails) || form.dataset.confirmDetails;
                    if (rawDetails) {
                        try { details = JSON.parse(rawDetails); } catch (_) {}
                    }

                    this.openConfirm({
                        type,
                        badge,
                        title,
                        message,
                        details,
                        confirmText,
                        onConfirm: () => {
                            form.dataset.confirmedBypass = '1';
                            if (submitter && submitter.hasAttribute('formaction')) {
                                form.action = submitter.getAttribute('formaction');
                            }
                            form.submit();
                        }
                    });
                });
            },

            pushToast(options) {
                const opts = typeof options === 'string' ? { message: options } : (options || {});
                const id = Date.now() + Math.random();
                const type = opts.type || 'info';
                const defaultTitles = {
                    success: 'Action Completed',
                    error: 'Error Occurred',
                    warning: 'Attention Required',
                    info: 'System Notice'
                };
                this.toasts.push({
                    id,
                    type,
                    title: opts.title || defaultTitles[type] || 'Notice',
                    message: opts.message || '',
                    details: Array.isArray(opts.details) ? opts.details : []
                });
                const duration = opts.duration || (type === 'error' ? 7000 : 4500);
                setTimeout(() => this.dismissToast(id), duration);
            },

            dismissToast(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            },

            openConfirm(options) {
                this.confirmModal = {
                    open: true,
                    type: options.type || 'primary',
                    badge: options.badge || 'Safety Confirmation Required',
                    title: options.title || 'Confirm Action?',
                    message: options.message || 'Are you sure you want to perform this action?',
                    details: Array.isArray(options.details) ? options.details : [],
                    confirmText: options.confirmText || 'Yes, Confirm',
                    cancelText: options.cancelText || 'No, Go Back',
                    onConfirm: typeof options.onConfirm === 'function' ? options.onConfirm : null,
                    onCancel: typeof options.onCancel === 'function' ? options.onCancel : null
                };
            },

            acceptConfirm() {
                const cb = this.confirmModal.onConfirm;
                this.confirmModal.open = false;
                if (cb) cb();
            },

            cancelConfirm() {
                const cb = this.confirmModal.onCancel;
                this.confirmModal.open = false;
                if (cb) cb();
            }
        };
    }
    </script>

    @stack('scripts')
</body>
</html>
