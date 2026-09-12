@extends('layouts.portal')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-8 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 p-8 text-white shadow-lg">
        <h1 class="text-3xl font-semibold">Welcome, {{ auth()->user()->name }}</h1>
        <p class="mt-2 text-indigo-100">Browse events, shop merchandise, and track your orders.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Recent orders</h2>
                <a href="{{ route('portal.orders.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">View all</a>
            </div>

            @forelse ($recentOrders as $order)
                <div class="flex items-center justify-between border-t border-slate-100 py-3 first:border-t-0 first:pt-0">
                    <div>
                        <p class="font-medium">Order #{{ $order->id }}</p>
                        <p class="text-sm text-slate-500">{{ ucfirst($order->status->value) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-medium">${{ number_format((float) $order->total, 2) }}</p>
                        <a href="{{ route('portal.orders.show', $order) }}" class="text-sm text-slate-600 hover:text-slate-900">Details</a>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No orders yet. Visit the shop to place your first order.</p>
            @endforelse
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Upcoming events</h2>
                <a href="{{ route('portal.events.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Browse events</a>
            </div>

            @forelse ($upcomingRegistrations as $registration)
                <div class="border-t border-slate-100 py-3 first:border-t-0 first:pt-0">
                    <p class="font-medium">{{ $registration->event?->title }}</p>
                    <p class="text-sm text-slate-500">
                        {{ $registration->event?->start_date?->format('M j, Y g:i A') }}
                    </p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No upcoming registrations. Explore published events to register.</p>
            @endforelse
        </section>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <a href="{{ route('portal.events.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-200 hover:shadow-md">
            <h3 class="font-semibold text-brand-700">Events</h3>
            <p class="mt-1 text-sm text-slate-500">Discover and register for events.</p>
        </a>
        <a href="{{ route('portal.products.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-200 hover:shadow-md">
            <h3 class="font-semibold text-brand-700">Shop</h3>
            <p class="mt-1 text-sm text-slate-500">Browse merchandise and add to cart.</p>
        </a>
        <a href="{{ route('portal.cart.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-200 hover:shadow-md">
            <h3 class="font-semibold text-brand-700">Cart</h3>
            <p class="mt-1 text-sm text-slate-500">Review items and checkout.</p>
        </a>
    </div>
@endsection
