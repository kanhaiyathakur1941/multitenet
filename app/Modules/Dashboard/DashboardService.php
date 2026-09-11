<?php

declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

final class DashboardService
{
    /**
     * @return array{
     *     total_events: int,
     *     published_events: int,
     *     total_products: int,
     *     total_customers: int,
     *     total_orders: int,
     *     total_revenue: string,
     *     upcoming_events: list<array<string, mixed>>,
     *     recent_orders: list<array<string, mixed>>
     * }
     */
    public function forTenant(User $user): array
    {
        $totalEvents = Event::query()->count();
        $publishedEvents = Event::query()->where('status', EventStatus::Published)->count();
        $totalProducts = Product::query()->count();
        $totalCustomers = User::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereHas('role', fn ($query) => $query->where('slug', UserRole::Customer->value))
            ->count();
        $totalOrders = Order::query()->count();

        $totalRevenue = Order::query()
            ->whereIn('status', [
                OrderStatus::Confirmed,
                OrderStatus::Processing,
                OrderStatus::Shipped,
                OrderStatus::Completed,
            ])
            ->sum('total');

        $upcomingEvents = Event::query()
            ->with('creator')
            ->where('status', EventStatus::Published)
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->limit(5)
            ->get();

        $recentOrders = Order::query()
            ->with(['user', 'items.product'])
            ->latest()
            ->limit(5)
            ->get();

        return [
            'total_events' => $totalEvents,
            'published_events' => $publishedEvents,
            'total_products' => $totalProducts,
            'total_customers' => $totalCustomers,
            'total_orders' => $totalOrders,
            'total_revenue' => number_format((float) $totalRevenue, 2, '.', ''),
            'upcoming_events' => $this->formatEvents($upcomingEvents),
            'recent_orders' => $this->formatOrders($recentOrders),
        ];
    }

    /**
     * @param  Collection<int, Event>  $events
     * @return list<array<string, mixed>>
     */
    private function formatEvents(Collection $events): array
    {
        return $events->map(fn (Event $event): array => [
            'id' => $event->id,
            'title' => $event->title,
            'location' => $event->location,
            'start_date' => $event->start_date?->toIso8601String(),
            'end_date' => $event->end_date?->toIso8601String(),
            'status' => $event->status->value,
            'created_by' => $event->relationLoaded('creator') && $event->creator !== null
                ? ['id' => $event->creator->id, 'name' => $event->creator->name]
                : null,
        ])->values()->all();
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return list<array<string, mixed>>
     */
    private function formatOrders(Collection $orders): array
    {
        return $orders->map(fn (Order $order): array => [
            'id' => $order->id,
            'status' => $order->status->value,
            'total' => $order->total,
            'user' => $order->relationLoaded('user') && $order->user !== null
                ? ['id' => $order->user->id, 'name' => $order->user->name]
                : null,
            'created_at' => $order->created_at?->toIso8601String(),
        ])->values()->all();
    }
}
