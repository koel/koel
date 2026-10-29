<?php

namespace App\Enums;

enum EmbeddableType: string
{
    case PLAYABLE = 'playable';
    case PLAYLIST = 'playlist';
    case ALBUM = 'album';
    case ARTIST = 'artist';
    case FAVORITES = 'favorites';
}
