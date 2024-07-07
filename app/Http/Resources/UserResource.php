<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'email',
        'avatar',
        'is_admin',
        'preferences',
        'is_prospect',
        'sso_provider',
        'sso_id',
    ];

    public function __construct(private readonly User $user)
    {
        parent::__construct($user);
    }

    /** @return array<mixed> */
    public function toArray($request): array
    {
        return [
            'type' => 'users',
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'avatar' => $this->user->avatar,
            'is_admin' => $this->user->is_admin,
            'preferences' => $this->user->preferences,
            'is_prospect' => $this->user->is_prospect,
            'sso_provider' => $this->user->sso_provider,
            'sso_id' => $this->user->sso_id,
        ];
    }
}
