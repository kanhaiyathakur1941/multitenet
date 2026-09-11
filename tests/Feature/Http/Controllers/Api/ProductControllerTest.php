<?php

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function productPayload(array $overrides = []): array
{
    return [
        'name' => 'Event T-Shirt',
        'description' => 'Soft cotton tee.',
        'sku' => 'TEE-001',
        'price' => 19.99,
        'stock' => 50,
        'status' => 'active',
        ...$overrides,
    ];
}

describe('index', function () {
    it('lists only the current tenant products for a tenant admin', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $adminA = User::factory()->tenantAdmin()->for($tenantA)->create();
        $own = Product::factory()->for($tenantA)->create(['name' => 'Alpha Mug']);
        Product::factory()->for($tenantB)->create(['name' => 'Beta Mug']);

        Sanctum::actingAs($adminA);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.id', $own->id);
    });

    it('lists only active products for a customer', function () {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin()->for($tenant)->create();
        $customer = User::factory()->customer()->for($tenant)->create();
        $active = Product::factory()->active()->for($tenant)->create(['name' => 'Visible']);
        Product::factory()->inactive()->for($tenant)->create(['name' => 'Hidden']);

        Sanctum::actingAs($customer);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.id', $active->id);
    });

    it('searches products by name', function () {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin()->for($tenant)->create();
        Product::factory()->for($tenant)->create(['name' => 'Premium Hoodie']);
        Product::factory()->for($tenant)->create(['name' => 'Basic Cap']);

        Sanctum::actingAs($admin);

        $this->getJson('/api/products?search=Hoodie')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.name', 'Premium Hoodie');
    });
});

describe('store', function () {
    it('creates a product for a tenant admin', function () {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin()->for($tenant)->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/products', productPayload(['sku' => 'SKU-100']))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Event T-Shirt')
            ->assertJsonPath('data.sku', 'SKU-100');

        $this->assertDatabaseHas('products', [
            'tenant_id' => $tenant->id,
            'sku' => 'SKU-100',
        ]);
    });

    it('returns 403 when a customer creates a product', function () {
        $customer = User::factory()->customer()->create();

        Sanctum::actingAs($customer);

        $this->postJson('/api/products', productPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('products', 0);
    });

    it('returns 403 when a manager creates a product', function () {
        $manager = User::factory()->manager()->create();

        Sanctum::actingAs($manager);

        $this->postJson('/api/products', productPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('products', 0);
    });

    it('returns 422 when the sku already exists for the tenant', function () {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin()->for($tenant)->create();
        Product::factory()->for($tenant)->create(['sku' => 'DUPLICATE']);

        Sanctum::actingAs($admin);

        $this->postJson('/api/products', productPayload(['sku' => 'DUPLICATE']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);
    });
});

describe('update', function () {
    it('returns 403 when a manager updates a product', function () {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->manager()->for($tenant)->create();
        $product = Product::factory()->for($tenant)->create();

        Sanctum::actingAs($manager);

        $this->putJson('/api/products/'.$product->id, productPayload(['sku' => $product->sku]))
            ->assertForbidden();

        expect($product->fresh()->name)->not->toBe('Updated Name');
    });

    it('returns 403 when a customer updates a product', function () {
        $tenant = Tenant::factory()->create();
        $customer = User::factory()->customer()->for($tenant)->create();
        $product = Product::factory()->active()->for($tenant)->create();

        Sanctum::actingAs($customer);

        $this->putJson('/api/products/'.$product->id, productPayload(['sku' => $product->sku, 'name' => 'Updated Name']))
            ->assertForbidden();

        expect($product->fresh()->name)->not->toBe('Updated Name');
    });
});

describe('show', function () {
    it('returns 404 when tenant A requests a tenant B product', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $adminA = User::factory()->tenantAdmin()->for($tenantA)->create();
        $productB = Product::factory()->for($tenantB)->create();

        Sanctum::actingAs($adminA);

        $this->getJson('/api/products/'.$productB->id)
            ->assertNotFound();
    });
});

describe('destroy', function () {
    it('returns 403 when a manager deletes a product', function () {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->manager()->for($tenant)->create();
        $product = Product::factory()->for($tenant)->create();

        Sanctum::actingAs($manager);

        $this->deleteJson('/api/products/'.$product->id)
            ->assertForbidden();

        $this->assertNotSoftDeleted($product);
    });

    it('returns 403 when a customer deletes a product', function () {
        $tenant = Tenant::factory()->create();
        $customer = User::factory()->customer()->for($tenant)->create();
        $product = Product::factory()->active()->for($tenant)->create();

        Sanctum::actingAs($customer);

        $this->deleteJson('/api/products/'.$product->id)
            ->assertForbidden();

        $this->assertNotSoftDeleted($product);
    });

    it('soft deletes a product for a tenant admin', function () {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin()->for($tenant)->create();
        $product = Product::factory()->for($tenant)->create();

        Sanctum::actingAs($admin);

        $this->deleteJson('/api/products/'.$product->id)
            ->assertOk();

        $this->assertSoftDeleted($product);
    });
});
