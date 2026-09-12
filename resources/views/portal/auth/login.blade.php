<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Login — {{ config('app.name') }}</title>
    @include('partials.portal-head')
</head>
<body class="min-h-screen bg-gradient-to-br from-brand-900 via-brand-700 to-slate-900 font-sans antialiased">
    <div class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-lg font-bold text-white ring-1 ring-white/20">
                    EF
                </div>
                <h1 class="text-3xl font-semibold text-white">{{ config('app.name') }}</h1>
                <p class="mt-2 text-sm text-indigo-100">Sign in to browse events, shop, and track orders</p>
            </div>

            <div class="rounded-2xl bg-white p-8 shadow-2xl shadow-slate-900/20">
                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('portal.login.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', config('eventflow.demo.customer_email')) }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100"
                        >
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            value="{{ old('password', 'password') }}"
                            required
                            autocomplete="current-password"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100"
                        >
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/30 hover:bg-brand-700">
                        Sign in
                    </button>
                </form>

                <div class="mt-6 rounded-xl bg-slate-50 px-4 py-3 text-center text-xs text-slate-600">
                    <p class="font-medium text-slate-700">Demo account (dummy data only)</p>
                    <p class="mt-1">
                        <span class="font-mono">{{ config('eventflow.demo.customer_email') }}</span>
                        /
                        <span class="font-mono">password</span>
                    </p>
                    <p class="mt-1 text-slate-500">
                        Razorpay checkout uses demo phone
                        <span class="font-mono">{{ config('eventflow.demo.customer_phone') }}</span>
                    </p>
                </div>

                <p class="mt-4 text-center text-xs text-slate-500">
                    Staff member?
                    <a href="{{ url('/admin') }}" class="font-medium text-brand-600 hover:text-brand-700">Go to admin panel</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
