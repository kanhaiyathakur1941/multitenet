<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        if (! User::query()->where('email', 'superadmin@eventflow.test')->exists()) {
            User::query()->create([
                'name' => 'Super Admin',
                'email' => 'superadmin@eventflow.test',
                'password' => 'password',
                'email_verified_at' => now(),
                'role_id' => Role::query()->where('slug', UserRole::SuperAdmin->value)->value('id'),
                'tenant_id' => null,
            ]);
        }

        $this->call(DemoDataSeeder::class);
    }
}
