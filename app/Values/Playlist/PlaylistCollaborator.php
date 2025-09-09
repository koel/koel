<?php

namespace App\Values\Playlist;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;

final class PlaylistCollaborator implements Arrayable
{
    private function __construct(public string $publicId, public string $name, public string $avatar)
    {
    }

    public static function make(string $publicId, string $name, string $avatar): self
    {
        return new self($publicId, $name, $avatar);
    }

    public static function fromUser(User $user): self
    {
        return new self($user->public_id, $user->name, $user->avatar);
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'type' => 'playlist_collaborators',
            'id' => $this->publicId,
            'name' => $this->name,
            'avatar' => $this->avatar,
        ];
    }
}
