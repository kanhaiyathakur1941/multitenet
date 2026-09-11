<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'slug' => fake()->unique()->slug(2),
        ];
    }

    public function fromEnum(UserRole $role): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $role->label(),
            'slug' => $role->value,
        ]);
    }
}
