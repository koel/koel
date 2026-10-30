<?php

namespace App\Enums;

use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\FavoritesEmbeddableResource;
use App\Http\Resources\PlaylistResource;
use App\Http\Resources\SongResource;
use Illuminate\Http\Resources\Json\JsonResource;

enum EmbeddableType: string
{
    case PLAYABLE = 'playable';
    case PLAYLIST = 'playlist';
    case ALBUM = 'album';
    case ARTIST = 'artist';
    case FAVORITES = 'favorites';

    /** @return class-string<JsonResource> */
    public function resourceClass(): string
    {
        return match ($this) {
            self::PLAYABLE => SongResource::class,
            self::PLAYLIST => PlaylistResource::class,
            self::ALBUM => AlbumResource::class,
            self::ARTIST => ArtistResource::class,
            self::FAVORITES => FavoritesEmbeddableResource::class,
        };
    }
}
