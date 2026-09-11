<?php

use App\Models\Tenant;
use App\Models\User;
use App\Shared\CurrentTenant;
use App\Shared\Exceptions\TenantContextMissingException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\Fixtures\TenantOwnedRecord;

beforeEach(function (): void {
    Schema::create('tenant_owned_records', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('tenant_id')->constrained();
        $table->string('name');
        $table->timestamps();
    });
});

it('includes the current tenant on me for a tenant user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->customer()->for($tenant)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.user.tenant.id', $tenant->id)
        ->assertJsonPath('data.user.tenant.slug', $tenant->slug);
});

it('does not include another tenant on me', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = User::factory()->customer()->for($tenantA)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.user.tenant.id', $tenantA->id)
        ->assertJsonMissing(['data' => ['user' => ['tenant' => ['id' => $tenantB->id]]]]);
});

it('returns a null tenant on me for a super admin', function () {
    $user = User::factory()->superAdmin()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.user.tenant', null);
});

it('returns 403 when a tenant user has no tenant', function () {
    $user = User::factory()->customer()->withoutTenant()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/auth/me')
        ->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Tenant context is required.');
});

it('returns 403 when the tenant is inactive', function () {
    $tenant = Tenant::factory()->inactive()->create();
    $user = User::factory()->customer()->for($tenant)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/auth/me')
        ->assertForbidden()
        ->assertJsonPath('message', 'This tenant is inactive.');
});

it('hides another tenant\'s records through the tenant scope', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    TenantOwnedRecord::withoutTenant()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'Alpha record',
    ]);

    $recordB = TenantOwnedRecord::withoutTenant()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Beta record',
    ]);

    app(CurrentTenant::class)->set($tenantA);

    $records = TenantOwnedRecord::query()->get();

    expect($records)->toHaveCount(1)
        ->and($records->first()->name)->toBe('Alpha record');

    expect(TenantOwnedRecord::query()->find($recordB->id))->toBeNull();
});

it('does not return tenant records when no tenant is set', function () {
    $tenant = Tenant::factory()->create();

    TenantOwnedRecord::withoutTenant()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Hidden record',
    ]);

    expect(TenantOwnedRecord::query()->count())->toBe(0);
});

it('assigns the current tenant when creating a tenant-owned record', function () {
    $tenant = Tenant::factory()->create();
    app(CurrentTenant::class)->set($tenant);

    $record = TenantOwnedRecord::query()->create([
        'name' => 'Assigned record',
    ]);

    expect($record->tenant_id)->toBe($tenant->id);
});

it('rejects creating a tenant-owned record without tenant context', function () {
    TenantOwnedRecord::query()->create([
        'name' => 'Orphan record',
    ]);
})->throws(TenantContextMissingException::class);
