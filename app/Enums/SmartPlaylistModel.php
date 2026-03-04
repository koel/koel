<?php

namespace App\Enums;

enum SmartPlaylistModel: string
{
    case ALBUM_NAME = 'album.name';
    case ARTIST_NAME = 'artist.name';
    case DATE_ADDED = 'created_at';
    case DATE_MODIFIED = 'updated_at';
    case GENRE = 'genre';
    case LAST_PLAYED = 'interactions.last_played_at';
    case LENGTH = 'length';
    case PLAY_COUNT = 'interactions.play_count';
    case TITLE = 'title';
    case USER_ID = 'interactions.user_id';
    case YEAR = 'year';

    public function toColumnName(): string
    {
        return match ($this) {
            self::ALBUM_NAME => 'songs.album_name',
            self::ARTIST_NAME => 'songs.artist_name',
            self::DATE_ADDED => 'songs.created_at',
            self::DATE_MODIFIED => 'songs.updated_at',
            self::GENRE => 'genres.name',
            self::LENGTH => 'songs.length',
            self::PLAY_COUNT => 'COALESCE(interactions.play_count, 0)',
            self::TITLE => 'songs.title',
            self::YEAR => 'songs.year',
            default => $this->value,
        };
    }

    public function isDate(): bool
    {
        return in_array($this, [self::LAST_PLAYED, self::DATE_ADDED, self::DATE_MODIFIED], true);
    }

    /**
     * Indicates whether this model would require a raw SQL query to be used in a smart playlist rule.
     * For example, the play count is a virtual column that needs to be queried with raw SQL.
     */
    public function requiresRawQuery(): bool
    {
        return $this === self::PLAY_COUNT;
    }

    public function getManyToManyRelation(): ?string
    {
        return match ($this) {
            self::GENRE => 'genres',
            default => null,
        };
    }
}
