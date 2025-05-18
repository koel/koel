<?php

namespace App\Services;

use App\Enums\PermissionableResourceType;
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

        /** @var class-string<Model> $modelClass */
        $modelClass = $type->value;

        return Gate::forUser($user ?? auth()->user())
            ->allows($action, $modelClass::query()->findOrFail($id));
    }
}
