@extends('layouts.portal')

@section('title', 'Shop')

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900">Shop</h1>
            <p class="mt-2 text-slate-600">Add merchandise to your cart and checkout.</p>
        </div>

        <form method="GET" action="{{ route('portal.products.index') }}" class="flex gap-2">
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search products"
                class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
            >
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Search</button>
        </form>
    </div>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($products as $product)
            <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold">{{ $product->name }}</h2>
                <p class="mt-2 flex-1 text-sm text-slate-600">{{ $product->description }}</p>
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-lg font-semibold">${{ number_format((float) $product->price, 2) }}</span>
                    <span class="text-sm text-slate-500">{{ $product->stock }} in stock</span>
                </div>

                <form method="POST" action="{{ route('portal.cart.store', $product) }}" class="mt-5 flex items-center gap-3">
                    @csrf
                    <input
                        type="number"
                        name="quantity"
                        value="1"
                        min="1"
                        max="{{ min($product->stock, 99) }}"
                        class="w-20 rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                    <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                        Add to cart
                    </button>
                </form>
            </article>
        @empty
            <p class="text-slate-500">No products available.</p>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $products->links() }}
    </div>
@endsection
