<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Event $event): bool
    {
        if ($user->canManageEvents()) {
            return true;
        }

        return $user->isCustomer() && $event->status === EventStatus::Published;
    }

    public function create(User $user): bool
    {
        return $user->canManageEvents();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->canManageEvents();
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->canManageEvents();
    }

    public function register(User $user, Event $event): bool
    {
        return $user->isCustomer() && $event->status === EventStatus::Published;
    }
}
