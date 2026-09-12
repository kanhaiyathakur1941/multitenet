@extends('layouts.portal')

@section('title', $event->title)

@section('content')
    <a href="{{ route('portal.events.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">← Back to events</a>

    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-8">
        <h1 class="text-3xl font-semibold text-slate-900">{{ $event->title }}</h1>
        <p class="mt-4 text-slate-600">{{ $event->description }}</p>

        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-slate-500">Start</dt>
                <dd class="mt-1">{{ $event->start_date->format('M j, Y g:i A') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">End</dt>
                <dd class="mt-1">{{ $event->end_date->format('M j, Y g:i A') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Location</dt>
                <dd class="mt-1">{{ $event->location }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Capacity</dt>
                <dd class="mt-1">{{ $event->capacity }}</dd>
            </div>
        </dl>

        @if ($isRegistered)
            <p class="mt-8 inline-flex rounded-full bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700">
                You are registered for this event.
            </p>
        @else
            <form method="POST" action="{{ route('portal.events.register', $event) }}" class="mt-8">
                @csrf
                <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    Register for event
                </button>
            </form>
        @endif
    </div>
@endsection
