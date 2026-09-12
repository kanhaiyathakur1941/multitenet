@extends('layouts.portal')

@section('title', 'Checkout')

@section('content')
    <a href="{{ route('portal.cart.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">← Back to cart</a>

    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-8">
        <h1 class="text-3xl font-semibold text-slate-900">Complete payment</h1>
        <p class="mt-2 text-slate-600">
            Order #{{ $order->id }} · {{ $currency }} {{ number_format((float) $order->total, 2) }}
        </p>

        <div class="mt-6 overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">Item</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">Qty</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-4 py-3">{{ $item->product?->name }}</td>
                            <td class="px-4 py-3">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right">{{ $currency }} {{ number_format((float) $item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
            <button
                type="button"
                id="razorpay-pay-button"
                class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
            >
                Pay with Razorpay
            </button>

            <a href="{{ route('portal.checkout.cancel', $order) }}" class="text-sm font-medium text-slate-600 underline hover:text-slate-900">
                Cancel payment
            </a>
        </div>

        <p class="mt-4 text-xs text-slate-500">
            Test mode: use Razorpay test cards from your dashboard. Amounts are charged in {{ $currency }}.
        </p>
    </div>

    <form id="razorpay-verify-form" method="POST" action="{{ route('portal.checkout.verify', $order) }}" class="hidden">
        @csrf
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
    </form>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        (function () {
            const options = {
                key: @json($keyId),
                amount: {{ $amount }},
                currency: @json($currency),
                name: @json(config('app.name')),
                description: 'Order #{{ $order->id }}',
                order_id: @json($order->razorpay_order_id),
                prefill: @json($prefill),
                theme: {
                    color: '#4f46e5',
                },
                handler: function (response) {
                    document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                    document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
                    document.getElementById('razorpay_signature').value = response.razorpay_signature;
                    document.getElementById('razorpay-verify-form').submit();
                },
                modal: {
                    ondismiss: function () {
                        window.location.href = @json(route('portal.checkout.cancel', $order));
                    },
                },
            };

            const rzp = new Razorpay(options);

            document.getElementById('razorpay-pay-button').addEventListener('click', function () {
                rzp.open();
            });

            rzp.open();
        })();
    </script>
@endsection
