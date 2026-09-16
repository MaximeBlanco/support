<?php

namespace App\Models\Builders;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<User>
 */
class UserBuilder extends Builder
{
    public function withPermission(Permission $permission): self
    {
        return $this->whereHas(
            'roles',
            fn (Builder $role): Builder => $role->whereHas(
                'permissions',
                fn (Builder $granted): Builder => $granted->where('name', $permission->value),
            ),
        );
    }

    public function withRole(RoleName $role): self
    {
        return $this->whereHas('roles', fn (Builder $query): Builder => $query->where('name', $role));
    }
}
