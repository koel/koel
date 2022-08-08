<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Values\AlbumInformation;
use App\Values\ArtistInformation;

class NullMusicEncyclopedia implements MusicEncyclopedia
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
