<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Orders\PaymentGatewayInterface;
use App\Modules\Orders\PaymentResult;
use Laravel\Sanctum\Sanctum;

describe('store', function () {
    it('creates an order with totals calculated on the server', function () {
        $tenant = Tenant::factory()->create();
        $customer = User::factory()->customer()->for($tenant)->create();
        $product = Product::factory()->for($tenant)->create([
            'price' => 10.00,
            'stock' => 10,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.subtotal', '20.00')
            ->assertJsonPath('data.tax', '2.00')
            ->assertJsonPath('data.total', '22.00')
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.items.0.price', '10.00')
            ->assertJsonPath('data.items.0.total', '20.00');

        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'tenant_id' => $tenant->id,
            'status' => OrderStatus::Confirmed->value,
            'total' => 22.00,
        ]);

        expect($product->fresh()->stock)->toBe(8);
    });

    it('returns 422 when a product is invalid for the tenant', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $customerA = User::factory()->customer()->for($tenantA)->create();
        $productB = Product::factory()->for($tenantB)->create();

        Sanctum::actingAs($customerA);

        $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $productB->id, 'quantity' => 1],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);

        $this->assertDatabaseCount('orders', 0);
    });

    it('returns 422 when stock is insufficient', function () {
        $tenant = Tenant::factory()->create();
        $customer = User::factory()->customer()->for($tenant)->create();
        $product = Product::factory()->for($tenant)->create([
            'stock' => 1,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.items.0', 'Insufficient stock for '.$product->name.'.');

        $this->assertDatabaseCount('orders', 0);
        expect($product->fresh()->stock)->toBe(1);
    });

    it('returns 422 when payment fails', function () {
        $tenant = Tenant::factory()->create();
        $customer = User::factory()->customer()->for($tenant)->create();
        $product = Product::factory()->for($tenant)->create(['stock' => 5]);

        $this->mock(PaymentGatewayInterface::class, function ($mock): void {
            $mock->shouldReceive('charge')
                ->once()
                ->andReturn(PaymentResult::failed('Payment was declined by the gateway.'));
        });

        Sanctum::actingAs($customer);

        $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment']);

        $this->assertDatabaseCount('orders', 0);
        expect($product->fresh()->stock)->toBe(5);
    });

    it('returns 403 when a manager creates an order', function () {
        $manager = User::factory()->manager()->create();

        Sanctum::actingAs($manager);

        $this->postJson('/api/orders', ['items' => []])
            ->assertForbidden();
    });

    it('returns 403 when a tenant admin creates an order', function () {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin()->for($tenant)->create();
        $product = Product::factory()->for($tenant)->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertForbidden();

        $this->assertDatabaseCount('orders', 0);
    });
});

describe('index', function () {
    it('lists only the customers own orders', function () {
        $tenant = Tenant::factory()->create();
        $customer = User::factory()->customer()->for($tenant)->create();
        $otherCustomer = User::factory()->customer()->for($tenant)->create();
        $own = Order::factory()->for($tenant)->for($customer)->create();
        Order::factory()->for($tenant)->for($otherCustomer)->create();

        Sanctum::actingAs($customer);

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.id', $own->id);
    });

    it('lists all tenant orders for a manager', function () {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->manager()->for($tenant)->create();
        $customer = User::factory()->customer()->for($tenant)->create();
        Order::factory()->count(2)->for($tenant)->for($customer)->create();

        Sanctum::actingAs($manager);

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });
});

describe('show', function () {
    it('returns 404 when tenant A requests a tenant B order', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $adminA = User::factory()->tenantAdmin()->for($tenantA)->create();
        $customerB = User::factory()->customer()->for($tenantB)->create();
        $orderB = Order::factory()->for($tenantB)->for($customerB)->create();

        Sanctum::actingAs($adminA);

        $this->getJson('/api/orders/'.$orderB->id)
            ->assertNotFound();
    });

    it('returns 403 when a customer views another customers order', function () {
        $tenant = Tenant::factory()->create();
        $customer = User::factory()->customer()->for($tenant)->create();
        $otherCustomer = User::factory()->customer()->for($tenant)->create();
        $order = Order::factory()->for($tenant)->for($otherCustomer)->create();

        Sanctum::actingAs($customer);

        $this->getJson('/api/orders/'.$order->id)
            ->assertForbidden();
    });
});
