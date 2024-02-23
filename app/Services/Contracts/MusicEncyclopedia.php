<?php

namespace App\Services\Contracts;

use App\Models\Album;
use App\Models\Artist;
use App\Values\AlbumInformation;
use App\Values\ArtistInformation;

interface MusicEncyclopedia
{
    public function getArtistInformation(Artist $artist): ?ArtistInformation;

    public function getAlbumInformation(Album $album): ?AlbumInformation;
}
