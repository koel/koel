<?php

namespace App\Enums;

use App\Models\Album;
use App\Models\Artist;
use InvalidArgumentException;

enum PermissionableResourceType: string
{
    case ALBUM = Album::class;
    case ARTIST = Artist::class;

    public static function resolve(string $type): self
    {
        return match ($type) {
            'album' => self::ALBUM,
            'artist' => self::ARTIST,
            default => throw new InvalidArgumentException("Invalid resource type: $type"),
        };
    }
}
