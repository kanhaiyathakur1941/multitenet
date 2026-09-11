<?php

declare(strict_types=1);

namespace App\Modules\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ProductService
{
    /**
     * @param  array{search?: string, status?: string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        $query = Product::query();

        if ($user->isCustomer()) {
            $query->where('status', ProductStatus::Active);
        } elseif (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $query->search($filters['search']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Product
    {
        return Product::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Product $product, array $attributes): Product
    {
        $product->update($attributes);

        return $product->refresh();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
