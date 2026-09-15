<?php

namespace Tests\Concerns;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

trait CreatesUsers
{
    protected function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function requester(): User
    {
        return User::factory()->withRole(RoleName::Requester)->create();
    }

    protected function technician(): User
    {
        return User::factory()->withRole(RoleName::Technician)->create();
    }

    protected function manager(): User
    {
        return User::factory()->withRole(RoleName::Manager)->create();
    }
}
