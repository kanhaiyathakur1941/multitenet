<?php

declare(strict_types=1);

namespace App\Modules\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EventService
{
    /**
     * @param  array{search?: string, status?: string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Event>
     */
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        $query = Event::query()->with('creator');

        if ($user->isCustomer()) {
            $query->where('status', EventStatus::Published);
        } elseif (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $query->search($filters['search']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy('start_date')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): Event
    {
        $event = Event::query()->create([
            ...$attributes,
            'created_by' => $user->id,
        ]);

        return $event->load('creator');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Event $event, array $attributes): Event
    {
        $event->update($attributes);

        return $event->refresh()->load('creator');
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }
}
