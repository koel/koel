<?php

namespace App\Enums;

use App\Models\Album;
use App\Models\Artist;
use App\Models\RadioStation;
use App\Models\User;

enum PermissionableResourceType: string
{
    case ALBUM = 'album';
    case ARTIST = 'artist';
    case RADIO_STATION = 'radio-station';
    case USER = 'user';

    /** @return class-string<Album|Artist|RadioStation|User> */
    public function modelClass(): string
    {
        return match ($this) {
            self::ALBUM => Album::class,
            self::ARTIST => Artist::class,
            self::RADIO_STATION => RadioStation::class,
            self::USER => User::class,
        };
    }
}
