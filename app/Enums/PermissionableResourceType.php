<?php

namespace App\Enums;

use App\Models\Album;
use App\Models\Artist;
use App\Models\RadioStation;
use InvalidArgumentException;

enum PermissionableResourceType: string
{
    case ALBUM = Album::class;
    case ARTIST = Artist::class;
    case RADIO_STATION = RadioStation::class;

    public static function resolve(string $type): self
    {
        return match ($type) {
            'album' => self::ALBUM,
            'artist' => self::ARTIST,
            'radio-station' => self::RADIO_STATION,
            default => throw new InvalidArgumentException("Invalid resource type: $type"),
        };
    }
}
