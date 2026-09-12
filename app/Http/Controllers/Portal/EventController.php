<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Modules\Events\EventService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class EventController extends Controller
{
    public function __construct(private EventService $events) {}

    public function index(Request $request): View
    {
        $events = $this->events->paginate($request->user(), [
            'search' => $request->string('search')->toString(),
            'per_page' => 12,
        ]);

        return view('portal.events.index', compact('events'));
    }

    public function show(Event $event): View
    {
        $this->authorize('view', $event);

        $event->loadMissing('creator');

        $isRegistered = $event->registrations()
            ->where('user_id', auth()->id())
            ->exists();

        return view('portal.events.show', [
            'event' => $event,
            'isRegistered' => $isRegistered,
        ]);
    }
}
