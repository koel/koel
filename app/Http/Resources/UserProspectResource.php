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
        'is_admin',
        'is_prospect',
    ];

    public function __construct(private User $user)
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
            'is_prospect' => $this->user->is_prospect,
        ];
    }
}
