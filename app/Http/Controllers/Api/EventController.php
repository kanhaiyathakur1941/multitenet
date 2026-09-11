<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\IndexEventRequest;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Modules\Events\EventService;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

final class EventController extends Controller
{
    public function __construct(private EventService $events) {}

    public function index(IndexEventRequest $request): JsonResponse
    {
        $paginator = $this->events->paginate($request->user(), $request->validated());

        return ApiResponse::paginated(
            'Events retrieved.',
            $paginator,
            EventResource::collection($paginator->getCollection())->resolve(),
        );
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->events->create($request->user(), $request->validated());

        return ApiResponse::success('Event created successfully.', EventResource::make($event)->resolve(), 201);
    }

    public function show(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $event->loadMissing('creator');

        return ApiResponse::success('Event retrieved.', EventResource::make($event)->resolve());
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $event = $this->events->update($event, $request->validated());

        return ApiResponse::success('Event updated successfully.', EventResource::make($event)->resolve());
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        $this->events->delete($event);

        return ApiResponse::success('Event deleted successfully.');
    }
}
