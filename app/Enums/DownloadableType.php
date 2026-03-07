<?php

namespace App\Enums;

enum DownloadableType: string
{
    case Songs = 'songs';
    case Album = 'album';
    case Artist = 'artist';
    case Playlist = 'playlist';
    case Favorites = 'favorites';
}
