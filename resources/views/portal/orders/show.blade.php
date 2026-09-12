@extends('layouts.portal')

@section('title', 'Order #'.$order->id)

@section('content')
    <a href="{{ route('portal.orders.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">← Back to orders</a>

    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Order #{{ $order->id }}</h1>
                <p class="mt-2 capitalize text-slate-600">Status: {{ $order->status->value }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm">
                <p><span class="text-slate-500">Payment:</span> {{ $order->payment_gateway ?? 'n/a' }}</p>
                @if ($order->razorpay_order_id)
                    <p class="mt-1 break-all"><span class="text-slate-500">Razorpay order:</span> {{ $order->razorpay_order_id }}</p>
                @endif
                <p class="mt-1 break-all"><span class="text-slate-500">Payment ID:</span> {{ $order->payment_transaction_id ?? 'n/a' }}</p>
            </div>
        </div>

        <div class="mt-8 overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">Item</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">Qty</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">Price</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-4 py-3">{{ $item->product?->name }}</td>
                            <td class="px-4 py-3">{{ $item->quantity }}</td>
                            <td class="px-4 py-3">${{ number_format((float) $item->price, 2) }}</td>
                            <td class="px-4 py-3 text-right">${{ number_format((float) $item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <dl class="mt-6 ml-auto max-w-xs space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd>${{ number_format((float) $order->subtotal, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Tax</dt><dd>${{ number_format((float) $order->tax, 2) }}</dd></div>
            <div class="flex justify-between text-base font-semibold"><dt>Total</dt><dd>${{ number_format((float) $order->total, 2) }}</dd></div>
        </dl>
    </div>
@endsection
