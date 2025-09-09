<?php

namespace App\Services\Contracts;

use App\Models\Album;
use App\Models\Artist;
use App\Values\Album\AlbumInformation;
use App\Values\Artist\ArtistInformation;

interface Encyclopedia
{
    public function getArtistInformation(Artist $artist): ?ArtistInformation;

    public function getAlbumInformation(Album $album): ?AlbumInformation;
}
