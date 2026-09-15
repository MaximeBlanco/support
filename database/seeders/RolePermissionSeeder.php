<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\RoleName;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(PermissionEnum::cases())
            ->mapWithKeys(fn (PermissionEnum $permission): array => [
                $permission->value => Permission::query()->firstOrCreate(['name' => $permission->value]),
            ]);

        foreach (RoleName::cases() as $roleName) {
            $role = Role::query()->firstOrCreate(['name' => $roleName->value]);

            $role->permissions()->sync(
                collect($roleName->permissions())
                    ->map(fn (PermissionEnum $permission): int => $permissions[$permission->value]->getKey())
                    ->all()
            );
        }
    }
}
