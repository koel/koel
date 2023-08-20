<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function __construct(private User $user, private bool $includePreferences = false)
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
            'preferences' => $this->when($this->includePreferences, $this->user->preferences),
            'is_prospect' => $this->user->is_prospect,
        ];
    }
}
