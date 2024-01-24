<?php

namespace App\Values;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;

final class PlaylistCollaborator implements Arrayable
{
    private function __construct(public int $id, public string $name, public string $avatar)
    {
    }

    public static function make(int $id, string $name, string $avatar): self
    {
        return new self($id, $name, $avatar);
    }

    public static function fromUser(User $user): self
    {
        return new self($user->id, $user->name, gravatar($user->email));
    }

    /** @return array<mixed> */
    public function toArray(): array
    {
        return [
            'type' => 'playlist_collaborators',
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->avatar,
        ];
    }
}
