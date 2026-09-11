<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->canManageEvents()) {
            return true;
        }

        return $user->isCustomer() && $order->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }
}
