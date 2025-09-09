<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\Contracts\Encyclopedia;
use App\Values\Album\AlbumInformation;
use App\Values\Artist\ArtistInformation;

class NullEncyclopedia implements Encyclopedia
{
    public function getArtistInformation(Artist $artist): ?ArtistInformation
    {
        return ArtistInformation::make();
    }

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        return AlbumInformation::make();
    }
}
