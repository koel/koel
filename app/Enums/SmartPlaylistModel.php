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
            self::TITLE => 'songs.title',
            self::LENGTH => 'songs.length',
            self::GENRE => 'genres.name',
            self::YEAR => 'songs.year',
            self::ALBUM_NAME => 'songs.album_name',
            self::ARTIST_NAME => 'songs.artist_name',
            self::DATE_ADDED => 'songs.created_at',
            self::DATE_MODIFIED => 'songs.updated_at',
            default => $this->value,
        };
    }

    public function isDate(): bool
    {
        return in_array($this, [self::LAST_PLAYED, self::DATE_ADDED, self::DATE_MODIFIED], true);
    }

    public function getManyToManyRelation(): ?string
    {
        return match ($this) {
            self::GENRE => 'genres',
            default => null,
        };
    }
}
