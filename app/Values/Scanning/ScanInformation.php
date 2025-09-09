<?php

namespace App\Values\Scanning;

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ScanInformation implements Arrayable
{
    private function __construct(
        public ?string $title,
        public ?string $albumName,
        public ?string $artistName,
        public ?string $albumArtistName,
        public ?int $track,
        public ?int $disc,
        public ?int $year,
        public ?string $genre,
        public ?string $lyrics,
        public ?float $length,
        public ?array $cover,
        public ?string $path,
        public ?string $hash,
        public ?int $mTime,
        public ?string $mimeType,
    ) {
    }

    public static function fromGetId3Info(array $info, string $path): self
    {
        // We prefer ID3v2 tags over ID3v1 tags.
        $tags = array_merge(
            Arr::get($info, 'tags.id3v1', []),
            Arr::get($info, 'tags.id3v2', []),
            Arr::get($info, 'comments', []),
        );

        $comments = Arr::get($info, 'comments', []);

        $albumArtistName = self::getTag($tags, ['albumartist', 'album_artist', 'band']);

        // If the song is explicitly marked as a compilation but there's no album artist name, use the umbrella
        // "Various Artists" artist.
        if (!$albumArtistName && self::getTag($tags, 'part_of_a_compilation')) {
            $albumArtistName = Artist::VARIOUS_NAME;
        }

        $cover = [self::getTag($comments, 'cover', null)];

        if ($cover[0] === null) {
            $cover = self::getTag($comments, 'picture', []);
        }

        $lyrics = html_entity_decode(self::getTag($tags, [
            'unsynchronised_lyric',
            'unsychronised_lyric',
            'unsyncedlyrics',
        ]));

        return new self(
            title: html_entity_decode(self::getTag($tags, 'title', pathinfo($path, PATHINFO_FILENAME))),
            albumName: html_entity_decode(self::getTag($tags, 'album', Album::UNKNOWN_NAME)),
            artistName: html_entity_decode(self::getTag($tags, 'artist', Artist::UNKNOWN_NAME)),
            albumArtistName: html_entity_decode($albumArtistName),
            track: (int) self::getTag($tags, ['track', 'tracknumber', 'track_number']),
            disc: (int) self::getTag($tags, ['discnumber', 'part_of_a_set'], 1),
            year: (int) self::getTag($tags, 'year') ?: null,
            genre: self::getTag($tags, 'genre'),
            lyrics: $lyrics,
            length: (float) Arr::get($info, 'playtime_seconds'),
            cover: $cover,
            path: $path,
            hash: File::hash($path),
            mTime: get_mtime($path),
            mimeType: Str::lower(Arr::get($info, 'mime_type')) ?: 'audio/mpeg',
        );
    }

    public static function make(
        ?string $title = null,
        ?string $albumName = null,
        ?string $artistName = null,
        ?string $albumArtistName = null,
        ?int $track = null,
        ?int $disc = null,
        ?int $year = null,
        ?string $genre = null,
        ?string $lyrics = null,
        ?float $length = null,
        ?array $cover = null,
        ?string $path = null,
        ?string $hash = null,
        ?int $mTime = null,
        ?string $mimeType = null,
    ): self {
        return new self(
            title: $title,
            albumName: $albumName,
            artistName: $artistName,
            albumArtistName: $albumArtistName,
            track: $track,
            disc: $disc,
            year: $year,
            genre: $genre,
            lyrics: $lyrics,
            length: $length,
            cover: $cover,
            path: $path,
            hash: $hash,
            mTime: $mTime,
            mimeType: $mimeType,
        );
    }

    private static function getTag(array $arr, string|array $keys, $default = ''): mixed
    {
        foreach (Arr::wrap($keys) as $name) {
            $value = Arr::get($arr, $name . '.0');

            if ($value) {
                break;
            }
        }

        return $value ?? $default;
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'album' => $this->albumName,
            'artist' => $this->artistName,
            'albumartist' => $this->albumArtistName,
            'track' => $this->track,
            'disc' => $this->disc,
            'year' => $this->year,
            'genre' => $this->genre,
            'lyrics' => $this->lyrics,
            'length' => $this->length,
            'cover' => $this->cover,
            'path' => $this->path,
            'hash' => $this->hash,
            'mtime' => $this->mTime,
            'mime_type' => $this->mimeType,
        ];
    }
}
