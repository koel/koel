<?php

namespace App\Services;

use App\Exceptions\NonSmartPlaylistException;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Database\Eloquent\Collection;

class SmartPlaylistService
{
    public function __construct(private readonly SongRepository $songRepository)
    {
    }

    /** @return Collection|array<array-key, Song> */
    public function getSongs(Playlist $playlist, User $user): Collection
    {
        throw_unless($playlist->is_smart, NonSmartPlaylistException::create($playlist));

        return $this->songRepository->getByPlaylist($playlist, $user);
    }
}
