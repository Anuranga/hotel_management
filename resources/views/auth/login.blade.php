@extends('layouts.app')

@section('title', 'Staff & Terminal Login — ' . $hotelName)

@section('content')
<div class="mx-auto flex min-h-[80vh] max-w-5xl items-center justify-center px-4 py-12" x-data="{ email: '{{ old('email', 'admin@hotel.local') }}', password: 'password' }">
    <div class="grid w-full gap-8 lg:grid-cols-12">
        <div class="rounded-3xl border border-slate-800 bg-slate-900 p-8 shadow-xl lg:col-span-7">
            <div class="mb-6">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-400">{{ $hotelName }}</span>
                <h1 class="mt-1 text-2xl font-extrabold text-white">Staff &amp; Role Terminal Sign-In</h1>
                <p class="mt-1 text-xs text-slate-400">Protected by CheckRole middleware across Admin, Manager, Cashier, Kitchen, and Staff stations.</p>
            </div>

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Work Email</label>
                    <input id="email" name="email" type="email" x-model="email" required autofocus
                           class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-amber-500 focus:outline-none">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Password</label>
                    <input id="password" name="password" type="password" x-model="password" required
                           class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-amber-500 focus:outline-none">
                </div>

                <button type="submit"
                        class="w-full rounded-xl bg-amber-500 px-5 py-3 text-sm font-bold text-slate-950 shadow transition hover:bg-amber-400">
                    Sign In to Station
                </button>
            </form>
        </div>

        <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 lg:col-span-5">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-300">Quick Demo Role Accounts</h2>
            <p class="mt-1 text-xs text-slate-400">Click any role below to autofill credentials (default password: <code class="text-amber-300">password</code>). Admin PIN: <code class="text-amber-300">9999</code>, Manager PIN: <code class="text-amber-300">1234</code>.</p>

            <div class="mt-4 space-y-2.5">
                @foreach ($demoUsers as $u)
                    <button type="button"
                            @click="email = '{{ $u->email }}'; password = 'password'"
                            class="flex w-full items-center justify-between rounded-xl border border-slate-800 bg-slate-950/80 px-4 py-3 text-left transition hover:border-amber-500/50">
                        <div>
                            <div class="text-sm font-bold text-white">{{ $u->name }}</div>
                            <div class="text-xs text-slate-400">{{ $u->email }}</div>
                        </div>
                        <span class="rounded-lg bg-amber-500/15 px-2.5 py-1 text-xs font-semibold uppercase text-amber-300">
                            {{ $u->role?->name }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

