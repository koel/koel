<?php

namespace App\Repositories;

use App\Models\Playlist;
use App\Repositories\Traits\ByCurrentUser;
use Illuminate\Support\Collection;

class PlaylistRepository extends Repository
{
    use ByCurrentUser;

    /**
     o* @return Collection
     */
    public function getAllByCurrentUser(): Collection
    {
        return $this->byCurrentUser()->orderBy('name')->get();
    }

    public function guessModelClass(): string
    {
        return Playlist::class;
    }
}
