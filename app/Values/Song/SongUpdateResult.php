<?php

namespace App\Values\Song;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Support\Collection;

final readonly class SongUpdateResult
{
    private function __construct(
        public Collection $updatedSongs,
        public Collection $removedArtistIds,
        public Collection $removedAlbumIds,
    ) {
    }

    public static function make(
        ?Collection $updatedSongs = null,
        ?Collection $removedArtistIds = null,
        ?Collection $removedAlbumIds = null,
    ): self {
        return new self(
            updatedSongs: $updatedSongs ?? collect(),
            removedArtistIds: $removedArtistIds ?? collect(),
            removedAlbumIds: $removedAlbumIds ?? collect(),
        );
    }

    public function addSong(Song $song): void
    {
        $this->updatedSongs->push($song);
    }

    public function addRemovedArtist(Artist $artist): void
    {
        if ($this->removedArtistIds->doesntContain($artist->id)) {
            $this->removedArtistIds->push($artist->id);
        }
    }

    public function addRemovedAlbum(Album $album): void
    {
        if ($this->removedAlbumIds->doesntContain($album->id)) {
            $this->removedAlbumIds->push($album->id);
        }
    }
}
