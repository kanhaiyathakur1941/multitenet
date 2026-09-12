@extends('layouts.portal')

@section('title', 'Orders')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-semibold text-slate-900">Your orders</h1>
        <p class="mt-2 text-slate-600">Track order status and payment references.</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left font-medium text-slate-600">Order</th>
                    <th class="px-6 py-3 text-left font-medium text-slate-600">Status</th>
                    <th class="px-6 py-3 text-left font-medium text-slate-600">Total</th>
                    <th class="px-6 py-3 text-left font-medium text-slate-600">Date</th>
                    <th class="px-6 py-3 text-right font-medium text-slate-600"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-6 py-4 font-medium">#{{ $order->id }}</td>
                        <td class="px-6 py-4 capitalize">{{ $order->status->value }}</td>
                        <td class="px-6 py-4">${{ number_format((float) $order->total, 2) }}</td>
                        <td class="px-6 py-4">{{ $order->created_at?->format('M j, Y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('portal.orders.show', $order) }}" class="font-medium text-slate-900 underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">No orders yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-8">
        {{ $orders->links() }}
    </div>
@endsection
