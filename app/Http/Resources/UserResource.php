<?php

namespace App\Http\Resources;

use App\Enums\Acl\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public const array JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'email',
        'avatar',
        'preferences',
        'is_prospect',
        'sso_provider',
        'sso_id',
        'is_admin',
        'role',
        'abilities',
        'permissions' => [
            'edit',
            'delete',
        ],
    ];

    public function __construct(
        private readonly User $user,
    ) {
        parent::__construct($user);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isCurrentUser = $this->user->is($viewer);

        return [
            'type' => 'users',
            'id' => $this->user->public_id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'avatar' => $this->user->avatar,
            'preferences' => $this->when($isCurrentUser, fn () => $this->user->preferences),
            'is_prospect' => $this->user->is_prospect,
            'sso_provider' => $this->user->sso_provider,
            'sso_id' => $this->user->sso_id,
            'is_admin' => $this->user->role === Role::ADMIN, // @todo remove this backward-compatibility field
            'role' => $this->user->role,
            'abilities' => $this->when($isCurrentUser, fn () => $this->user
                ->getPermissionsViaRoles()
                ->pluck('name')
                ->toArray()),
            'permissions' => [
                'edit' => $viewer->can('update', $this->user),
                'delete' => $viewer->can('destroy', $this->user),
            ],
        ];
    }
}
