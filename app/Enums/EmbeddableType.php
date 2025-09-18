<?php

namespace App\Enums;

use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\PlaylistResource;
use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use Illuminate\Http\Resources\Json\JsonResource;

enum EmbeddableType: string
{
    case PLAYABLE = 'playable';
    case PLAYLIST = 'playlist';
    case ALBUM = 'album';
    case ARTIST = 'artist';

    /** @return class-string<Song|Playlist|Album|Artist> */
    public function modelClass(): string
    {
        return match ($this) {
            self::PLAYABLE => Song::class,
            self::PLAYLIST => Playlist::class,
            self::ALBUM => Album::class,
            self::ARTIST => Artist::class,
        };
    }

    /** @return class-string<JsonResource> */
    public function resourceClass(): string
    {
        return match ($this) {
            self::PLAYABLE => SongResource::class,
            self::PLAYLIST => PlaylistResource::class,
            self::ALBUM => AlbumResource::class,
            self::ARTIST => ArtistResource::class,
        };
    }
}
