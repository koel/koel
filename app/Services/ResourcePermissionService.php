<?php

namespace App\Services;

use App\Enums\PermissionableResourceType;
use App\Models\Contracts\Permissionable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Webmozart\Assert\Assert;

class ResourcePermissionService
{
    private const VALID_ACTIONS = [
        'edit',
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

    private static function resolveResource(
        PermissionableResourceType $type,
        int|string $id,
    ): Model {
        /** @var class-string<Model|Permissionable> $modelClass */
        $modelClass = $type->value;

        return $modelClass::query()->where($modelClass::getPermissionableIdentifier(), $id)->firstOrFail(); // @phpstan-ignore-line
    }
}
