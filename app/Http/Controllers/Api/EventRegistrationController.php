<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\RegisterForEventRequest;
use App\Http\Resources\EventRegistrationResource;
use App\Models\Event;
use App\Modules\Events\RegisterForEventService;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

final class EventRegistrationController extends Controller
{
    public function store(
        RegisterForEventRequest $request,
        Event $event,
        RegisterForEventService $registrations,
    ): JsonResponse {
        $registration = $registrations->register($event, $request->user());

        return ApiResponse::success(
            'Registered for the event successfully.',
            EventRegistrationResource::make($registration)->resolve(),
            201,
        );
    }
}
