<?php

namespace App\Repositories;

use App\Models\Playlist;
use App\Repositories\Traits\ByCurrentUser;
use Illuminate\Support\Collection;

class PlaylistRepository extends Repository
{
    use ByCurrentUser;

    /** @return Collection|array<Playlist> */
    public function getAllByCurrentUser(): Collection
    {
        return $this->byCurrentUser()->orderBy('name')->get();
    }
}
