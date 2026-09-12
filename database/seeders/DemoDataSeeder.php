<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
            $this->createUser(
                name: "{$name} Admin",
                email: $adminEmail,
                role: UserRole::TenantAdmin,
                tenant: $tenant,
            );
        }

        if (! User::query()->where('email', $managerEmail)->exists()) {
            $this->createUser(
                name: "{$name} Manager",
                email: $managerEmail,
                role: UserRole::Manager,
                tenant: $tenant,
            );
        }

        $manager = User::query()->where('email', $managerEmail)->firstOrFail();

        if (! User::query()->where('email', $customerEmail)->exists()) {
            $customer = $this->createUser(
                name: "{$name} Customer",
                email: $customerEmail,
                role: UserRole::Customer,
                tenant: $tenant,
            );
        } else {
            $customer = User::query()->where('email', $customerEmail)->firstOrFail();
        }

        if (Event::query()->where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $this->seedEvents($tenant, $manager);
        $products = $this->seedProducts($tenant);

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
    }

    private function createUser(
        string $name,
        string $email,
        UserRole $role,
        ?Tenant $tenant = null,
    ): User {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'email_verified_at' => now(),
            'role_id' => Role::query()->where('slug', $role->value)->value('id'),
            'tenant_id' => $tenant?->id,
        ]);
    }

    private function seedEvents(Tenant $tenant, User $manager): void
    {
        $events = [
            ['title' => 'Annual Summit', 'status' => EventStatus::Published],
            ['title' => 'Community Meetup', 'status' => EventStatus::Published],
            ['title' => 'Workshop Series', 'status' => EventStatus::Draft],
            ['title' => 'Product Launch', 'status' => EventStatus::Published],
            ['title' => 'Year End Gala', 'status' => EventStatus::Completed],
        ];

        foreach ($events as $index => $event) {
            $start = now()->addWeeks($index + 1);

            Event::query()->create([
                'tenant_id' => $tenant->id,
                'created_by' => $manager->id,
                'title' => $event['title'],
                'description' => "Demo event: {$event['title']}",
                'location' => 'Main Hall',
                'start_date' => $start,
                'end_date' => $start->copy()->addHours(3),
                'capacity' => 100,
                'status' => $event['status'],
            ]);
        }
    }

    /**
     * @return Collection<int, Product>
     */
    private function seedProducts(Tenant $tenant): Collection
    {
        $products = [
            ['name' => 'Event T-Shirt', 'price' => 24.99, 'stock' => 50],
            ['name' => 'Branded Cap', 'price' => 18.50, 'stock' => 40],
            ['name' => 'Conference Mug', 'price' => 12.00, 'stock' => 60],
            ['name' => 'Sticker Pack', 'price' => 5.99, 'stock' => 100],
            ['name' => 'Notebook Set', 'price' => 15.00, 'stock' => 35],
            ['name' => 'Water Bottle', 'price' => 22.00, 'stock' => 45],
            ['name' => 'Tote Bag', 'price' => 19.99, 'stock' => 30],
            ['name' => 'Lanyard', 'price' => 4.50, 'stock' => 80],
            ['name' => 'Hoodie', 'price' => 49.99, 'stock' => 25],
            ['name' => 'Pin Badge', 'price' => 3.99, 'stock' => 120],
        ];

        return collect($products)->map(function (array $product, int $index) use ($tenant): Product {
            return Product::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $product['name'],
                'description' => "Demo merchandise: {$product['name']}",
                'sku' => strtoupper(Str::slug($tenant->slug, '')).str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'price' => $product['price'],
                'stock' => $product['stock'],
                'status' => ProductStatus::Active,
            ]);
        });
    }
}
