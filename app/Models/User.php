<?php

namespace App\Models;

use App\Enums\Permission as PermissionEnum;
use App\Enums\RoleName;
use App\Models\Builders\UserBuilder;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[UseEloquentBuilder(UserBuilder::class)]
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var array<int, string>|null */
    private ?array $cachedPermissions = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function requestedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assignee_id');
    }

    /**
     * @return array<int, string>
     */
    public function permissionNames(): array
    {
        return $this->cachedPermissions ??= $this->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn (Role $role): array => $role->permissions->pluck('name')->all())
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermission(PermissionEnum $permission): bool
    {
        return in_array($permission->value, $this->permissionNames(), true);
    }

    public function hasRole(RoleName $role): bool
    {
        return $this->roles->contains(fn (Role $candidate): bool => $candidate->name === $role);
    }

    public function forgetPermissionCache(): void
    {
        $this->cachedPermissions = null;
        $this->unsetRelation('roles');
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }
}
