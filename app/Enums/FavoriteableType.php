<?php

namespace App\Enums;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Contracts\Favoriteable;
use App\Models\Podcast;
use App\Models\RadioStation;
use App\Models\Song;
use Illuminate\Database\Eloquent\Model;

enum FavoriteableType: string
{
    case PLAYABLE = 'playable';
    case ALBUM = 'album';
    case ARTIST = 'artist';
    case PODCAST = 'podcast';
    case RADIO_STATION = 'radio-station';

    /** @return class-string<Favoriteable|Model> */
    public function modelClass(): string
    {
        return match ($this) {
            self::PLAYABLE => Song::class,
            self::ALBUM => Album::class,
            self::ARTIST => Artist::class,
            self::PODCAST => Podcast::class,
            self::RADIO_STATION => RadioStation::class,
        };
    }
}
