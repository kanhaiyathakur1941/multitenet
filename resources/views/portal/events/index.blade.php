@extends('layouts.portal')

@section('title', 'Events')

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900">Events</h1>
            <p class="mt-2 text-slate-600">Published events you can register for.</p>
        </div>

        <form method="GET" action="{{ route('portal.events.index') }}" class="flex gap-2">
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search events"
                class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
            >
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Search</button>
        </form>
    </div>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($events as $event)
            <article class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold">{{ $event->title }}</h2>
                <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $event->description }}</p>
                <dl class="mt-4 space-y-1 text-sm text-slate-500">
                    <div><span class="font-medium text-slate-700">When:</span> {{ $event->start_date->format('M j, Y g:i A') }}</div>
                    <div><span class="font-medium text-slate-700">Where:</span> {{ $event->location }}</div>
                    <div><span class="font-medium text-slate-700">Capacity:</span> {{ $event->capacity }}</div>
                </dl>
                <a href="{{ route('portal.events.show', $event) }}" class="mt-5 inline-flex text-sm font-medium text-slate-900 underline">
                    View details
                </a>
            </article>
        @empty
            <p class="text-slate-500">No published events found.</p>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $events->links() }}
    </div>
@endsection
