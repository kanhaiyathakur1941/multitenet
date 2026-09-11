<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'remember_token' => Str::random(10),
            'tenant_id' => Tenant::factory(),
            'role_id' => $this->roleId(UserRole::Customer),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->forRole(UserRole::SuperAdmin)->state(fn (array $attributes): array => [
            'tenant_id' => null,
        ]);
    }

    public function withoutTenant(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tenant_id' => null,
        ]);
    }

    public function tenantAdmin(): static
    {
        return $this->forRole(UserRole::TenantAdmin);
    }

    public function manager(): static
    {
        return $this->forRole(UserRole::Manager);
    }

    public function customer(): static
    {
        return $this->forRole(UserRole::Customer);
    }

    public function forRole(UserRole $role): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_id' => $this->roleId($role),
        ]);
    }

    private function roleId(UserRole $role): int
    {
        return Role::query()->firstOrCreate(
            ['slug' => $role->value],
            ['name' => $role->label()],
        )->id;
    }
}
