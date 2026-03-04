<?php

namespace App\Models\Concerns\Playlists;

use App\Models\User;

trait ManagesCollaborators
{
    public function addCollaborator(User $user): void
    {
        if (!$this->hasCollaborator($user)) {
            $this->users()->attach($user, ['role' => 'collaborator']);
        }
    }

    public function hasCollaborator(User $collaborator): bool
    {
        return $this->collaborators->contains($collaborator->is(...));
    }
}
