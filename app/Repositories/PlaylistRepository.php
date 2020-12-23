<?php

namespace App\Repositories;

use App\Models\Playlist;
use App\Repositories\Traits\ByCurrentUser;
use App\Repositories\Traits\Searchable;
use Illuminate\Support\Collection;

class PlaylistRepository extends AbstractRepository
{
    use ByCurrentUser;
    use Searchable;

    /** @return Collection|array<Playlist> */
    public function getAllByCurrentUser(): Collection
    {
        return $this->byCurrentUser()->orderBy('name')->get();
    }
}
