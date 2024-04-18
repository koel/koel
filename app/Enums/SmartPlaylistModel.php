<?php

namespace App\Enums;

enum SmartPlaylistModel: string
{
    case TITLE = 'title';
    case ALBUM_NAME = 'album.name';
    case ARTIST_NAME = 'artist.name';
    case PLAY_COUNT = 'interactions.play_count';
    case LAST_PLAYED = 'interactions.last_played_at';
    case USER_ID = 'interactions.user_id';
    case LENGTH = 'length';
    case DATE_ADDED = 'created_at';
    case DATE_MODIFIED = 'updated_at';
    case GENRE = 'genre';
    case YEAR = 'year';

    public function toColumnName(): string
    {
        return match ($this) {
            self::ALBUM_NAME => 'albums.name',
            self::ARTIST_NAME => 'artists.name',
            self::DATE_ADDED => 'songs.created_at',
            self::DATE_MODIFIED => 'songs.updated_at',
            default => $this->value,
        };
    }

    public function isDate(): bool
    {
        return in_array($this, [self::LAST_PLAYED, self::DATE_ADDED, self::DATE_MODIFIED], true);
    }
}
