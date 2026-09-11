<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Dashboard\DashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EventFlowStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        if ($user->isSuperAdmin()) {
            return [
                Stat::make('Tenants', (string) Tenant::query()->count()),
                Stat::make('Users', (string) User::query()->count()),
                Stat::make('Orders', (string) Order::query()->withoutGlobalScopes()->count()),
            ];
        }

        $stats = app(DashboardService::class)->forTenant($user);

        return [
            Stat::make('Events', (string) $stats['total_events']),
            Stat::make('Published events', (string) $stats['published_events']),
            Stat::make('Products', (string) $stats['total_products']),
            Stat::make('Customers', (string) $stats['total_customers']),
            Stat::make('Orders', (string) $stats['total_orders']),
            Stat::make('Revenue', '$'.$stats['total_revenue']),
        ];
    }
}
