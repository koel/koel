<?php

namespace App\Services;

use App\Enums\PermissionableResourceType;
use App\Models\User;
use Illuminate\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Webmozart\Assert\Assert;

class ResourcePermissionService
{
    private const VALID_ACTIONS = [
        'edit',
    ];

    public function __construct(private readonly Gate $gate)
    {
    }

    public function checkPermission(
        PermissionableResourceType $type,
        int|string $id,
        string $action,
        ?User $user = null,
    ): bool {
        Assert::inArray($action, self::VALID_ACTIONS);

        /** @var class-string<Model> $modelClass */
        $modelClass = $type->value;

        return $this->gate
            ->forUser($user ?? auth()->user())
            ->allows($action, $modelClass::query()->findOrFail($id));
    }
}
