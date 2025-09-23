<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProspectResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'email',
        'avatar',
        'role',
        'is_prospect',
    ];

    public function __construct(private readonly User $user)
    {
        parent::__construct($user);
    }

    /** @inheritdoc */
    public function toArray($request): array
    {
        return [
            'type' => 'users',
            'id' => $this->user->public_id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'avatar' => $this->user->avatar,
            'role' => $this->user->role->value,
            'is_prospect' => $this->user->is_prospect,
        ];
    }
}
