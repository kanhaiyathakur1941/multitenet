<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Modules\Events\RegisterForEventService;
use Illuminate\Http\RedirectResponse;

final class EventRegistrationController extends Controller
{
    public function store(Event $event, RegisterForEventService $registrations): RedirectResponse
    {
        $this->authorize('register', $event);

        $registrations->register($event, auth()->user());

        return redirect()
            ->route('portal.events.show', $event)
            ->with('success', 'You are registered for this event.');
    }
}
