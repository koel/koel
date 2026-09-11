<?php

namespace App\Events;

use App\Models\Album;

class AlbumMbidsResolved extends Event
{
    /** @param array<mixed> $tracks The release's tracks, as returned by MusicBrainz */
    public function __construct(
        public Album $album,
        public string $mbid,
        public array $tracks = [],
    ) {}
}
