@extends('layouts.portal')

@section('title', 'Cart')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-semibold text-slate-900">Shopping cart</h1>
        <p class="mt-2 text-slate-600">Review your items before checkout.</p>
    </div>

    @if ($lines->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <p class="text-slate-500">Your cart is empty.</p>
            <a href="{{ route('portal.products.index') }}" class="mt-4 inline-flex text-sm font-medium text-slate-900 underline">
                Browse products
            </a>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left font-medium text-slate-600">Product</th>
                        <th class="px-6 py-3 text-left font-medium text-slate-600">Price</th>
                        <th class="px-6 py-3 text-left font-medium text-slate-600">Quantity</th>
                        <th class="px-6 py-3 text-right font-medium text-slate-600">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($lines as $line)
                        <tr>
                            <td class="px-6 py-4 font-medium">{{ $line['product']->name }}</td>
                            <td class="px-6 py-4">${{ number_format((float) $line['product']->price, 2) }}</td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('portal.cart.update', $line['product']) }}" class="inline-flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input
                                        type="number"
                                        name="quantity"
                                        value="{{ $line['quantity'] }}"
                                        min="0"
                                        max="99"
                                        class="w-20 rounded-lg border border-slate-300 px-2 py-1"
                                    >
                                    <button type="submit" class="text-slate-600 underline">Update</button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-right">${{ number_format($line['line_total'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 sm:flex-row sm:items-center sm:justify-between">
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between gap-8"><dt class="text-slate-500">Subtotal</dt><dd>${{ number_format($subtotal, 2) }}</dd></div>
                <div class="flex justify-between gap-8"><dt class="text-slate-500">Tax</dt><dd>${{ number_format($tax, 2) }}</dd></div>
                <div class="flex justify-between gap-8 text-base font-semibold"><dt>Total</dt><dd>${{ number_format($total, 2) }}</dd></div>
            </dl>

            <form method="POST" action="{{ route('portal.cart.checkout') }}">
                @csrf
                <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    @if ($usesRazorpay)
                        Pay with Razorpay
                    @else
                        Place order
                    @endif
                </button>
            </form>

            @if ($usesRazorpay)
                <p class="text-xs text-slate-500 sm:max-w-xs">
                    You will be redirected to Razorpay to complete payment in {{ config('eventflow.payments.razorpay.currency', 'INR') }}.
                </p>
            @endif
        </div>
    @endif
@endsection
