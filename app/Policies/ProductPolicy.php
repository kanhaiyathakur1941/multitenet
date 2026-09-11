<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Product $product): bool
    {
        if ($user->canManageProducts() || $user->canManageEvents()) {
            return true;
        }

        return $user->isCustomer() && $product->status === ProductStatus::Active;
    }

    public function create(User $user): bool
    {
        return $user->canManageProducts();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->canManageProducts();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->canManageProducts();
    }
}
