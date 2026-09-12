<?php

declare(strict_types=1);

namespace App\Modules\Portal;

use Illuminate\Contracts\Session\Session;

final class PortalCart
{
    private const SESSION_KEY = 'portal.cart';

    public function __construct(private Session $session) {}

    /**
     * @return array<int, int>
     */
    public function items(): array
    {
        /** @var array<int, int> $items */
        $items = $this->session->get(self::SESSION_KEY, []);

        return $items;
    }

    public function count(): int
    {
        return array_sum($this->items());
    }

    public function add(int $productId, int $quantity): void
    {
        $items = $this->items();
        $items[$productId] = ($items[$productId] ?? 0) + $quantity;
        $this->session->put(self::SESSION_KEY, $items);
    }

    public function update(int $productId, int $quantity): void
    {
        $items = $this->items();

        if ($quantity <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = $quantity;
        }

        $this->session->put(self::SESSION_KEY, $items);
    }

    public function remove(int $productId): void
    {
        $items = $this->items();
        unset($items[$productId]);
        $this->session->put(self::SESSION_KEY, $items);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * @return list<array{product_id: int, quantity: int}>
     */
    public function toOrderItems(): array
    {
        return collect($this->items())
            ->map(fn (int $quantity, int $productId): array => [
                'product_id' => $productId,
                'quantity' => $quantity,
            ])
            ->values()
            ->all();
    }
}
