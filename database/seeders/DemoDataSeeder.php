<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTenant(
            name: 'Alpha Events',
            slug: 'alpha-events',
            adminEmail: 'admin@alpha.eventflow.test',
            managerEmail: 'manager@alpha.eventflow.test',
            customerEmail: 'customer@eventflow.test',
        );

        $this->seedTenant(
            name: 'Beta Gatherings',
            slug: 'beta-gatherings',
            adminEmail: 'admin@beta.eventflow.test',
            managerEmail: 'manager@beta.eventflow.test',
            customerEmail: 'customer@beta.eventflow.test',
        );
    }

    private function seedTenant(
        string $name,
        string $slug,
        string $adminEmail,
        string $managerEmail,
        string $customerEmail,
    ): void {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'is_active' => true],
        );

        if (! User::query()->where('email', $adminEmail)->exists()) {
            User::factory()->tenantAdmin()->for($tenant)->create([
                'name' => "{$name} Admin",
                'email' => $adminEmail,
            ]);
        }

        if (! User::query()->where('email', $managerEmail)->exists()) {
            User::factory()->manager()->for($tenant)->create([
                'name' => "{$name} Manager",
                'email' => $managerEmail,
            ]);
        }

        $manager = User::query()->where('email', $managerEmail)->firstOrFail();

        if (! User::query()->where('email', $customerEmail)->exists()) {
            $customer = User::factory()->customer()->for($tenant)->create([
                'name' => "{$name} Customer",
                'email' => $customerEmail,
            ]);
        } else {
            $customer = User::query()->where('email', $customerEmail)->firstOrFail();
        }

        if (Event::query()->where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $events = Event::factory()
            ->count(5)
            ->for($tenant)
            ->for($manager, 'creator')
            ->sequence(
                ['status' => EventStatus::Published],
                ['status' => EventStatus::Published],
                ['status' => EventStatus::Draft],
                ['status' => EventStatus::Published],
                ['status' => EventStatus::Completed],
            )
            ->create();

        $products = Product::factory()
            ->count(10)
            ->for($tenant)
            ->active()
            ->create();

        $featuredProduct = $products->first();
        $subtotal = round((float) $featuredProduct->price * 2, 2);
        $tax = round($subtotal * (float) config('eventflow.tax_rate'), 2);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $customer->id,
            'status' => OrderStatus::Confirmed,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $featuredProduct->id,
            'quantity' => 2,
            'price' => $featuredProduct->price,
            'total' => $subtotal,
        ]);

        $featuredProduct->decrement('stock', 2);

        Event::query()
            ->whereKey($events->take(3)->pluck('id'))
            ->update(['status' => EventStatus::Published]);

        Product::query()
            ->whereKey($products->take(8)->pluck('id'))
            ->update(['status' => ProductStatus::Active]);
    }
}
