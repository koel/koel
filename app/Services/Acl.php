<?php

namespace App\Services;

use App\Enums\Acl\Role;
use App\Enums\PermissionableResourceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Webmozart\Assert\Assert;

class Acl
{
    private const VALID_ACTIONS = [
        'edit',
        'delete',
    ];

    public function checkPermission(
        PermissionableResourceType $type,
        int|string $id,
        string $action,
        ?User $user = null,
    ): bool {
        Assert::inArray($action, self::VALID_ACTIONS);

        return Gate::forUser($user ?? auth()->user())
            ->allows($action, self::resolveResource($type, $id));
    }

    private static function resolveResource(PermissionableResourceType $type, int|string $id): Model
    {
        $modelClass = $type->modelClass();

        return $modelClass::query()->where($modelClass::getPermissionableIdentifier(), $id)->firstOrFail(); // @phpstan-ignore-line
    }

    /** @return Collection<Role> */
    public function getAssignableRolesForUser(User $user): Collection
    {
        return Role::allAvailable()->filter(static fn (Role $role) => $user->role->canManage($role))
            ->sortBy(static fn (Role $role) => $role->level())
            ->values();
    }
}
