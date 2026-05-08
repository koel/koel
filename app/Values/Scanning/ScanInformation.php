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
    /**
     * Encodings to try, in order, when an extracted tag value is not valid UTF-8.
     *
     * - GB18030 first: a superset of GB2312/GBK that fixes the most common CJK case (#1816).
     * - Windows-1252 last: catches Latin-1 / Western-European mojibake (Café → Café).
     *
     * Other CJK encodings (Big5, Shift_JIS, EUC-JP, EUC-KR) are deliberately omitted: their
     * byte patterns overlap with GB18030's, so adding them in any order produces false
     * positives in one direction or the other. Adding them is a future iteration once a
     * concrete bug report justifies the trade-off.
     */
    private const array FALLBACK_ENCODINGS = [
        'GB18030',
        'Windows-1252',
    ];

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
        public ?int $fileSize,
    ) {}

    public static function fromGetId3Info(array $info, string $path): self
    {
        // We prefer ID3v2 tags over ID3v1 tags.
        $tags = array_merge(
            Arr::get($info, 'tags.id3v1', []),
            Arr::get($info, 'tags.id3v2', []),
            Arr::get($info, 'comments', []),
            Arr::get($info, 'tags.vorbiscomment', []),
        );

        $comments = Arr::get($info, 'comments', []);

        $albumArtistName = self::fixEncoding(self::getTag($tags, ['albumartist', 'album_artist', 'band']));

        // If the song is explicitly marked as a compilation but there's no album artist name, use the umbrella
        // "Various Artists" artist.
        if (!$albumArtistName && self::getTag($tags, 'part_of_a_compilation')) {
            $albumArtistName = Artist::VARIOUS_NAME;
        }

        $cover = [self::getTag($comments, 'cover', null)];

        if ($cover[0] === null) {
            $cover = self::getTag($comments, 'picture', []);
        }

        $lyrics = html_entity_decode(self::fixEncoding(self::getTag($tags, [
            'unsynchronised_lyric',
            'unsychronised_lyric',
            'unsyncedlyrics',
            'lyrics',
        ])));

        return new self(
            title: html_entity_decode(self::fixEncoding(self::getTag(
                $tags,
                'title',
                pathinfo($path, PATHINFO_FILENAME),
            ))),
            albumName: html_entity_decode(self::fixEncoding(self::getTag($tags, 'album', Album::UNKNOWN_NAME))),
            artistName: html_entity_decode(self::fixEncoding(self::getTag($tags, 'artist', Artist::UNKNOWN_NAME))),
            albumArtistName: html_entity_decode($albumArtistName),
            track: (int) self::getTag($tags, ['track', 'tracknumber', 'track_number']),
            disc: (int) self::getTag($tags, ['discnumber', 'part_of_a_set'], 1),
            year: (int) self::getTag($tags, 'year') ?: null,
            genre: self::fixEncoding(self::getTag($tags, 'genre')),
            lyrics: $lyrics,
            length: (float) Arr::get($info, 'playtime_seconds'),
            cover: $cover,
            path: $path,
            hash: File::hash($path),
            mTime: get_mtime($path),
            mimeType: Str::lower(Arr::get($info, 'mime_type')) ?: 'audio/mpeg',
            fileSize: File::size($path),
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
        ?int $fileSize = null,
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
            fileSize: $fileSize,
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

    /**
     * Recover the UTF-8 form of a tag string that getID3 returned as raw bytes
     * because the source ID3 frame's encoding marker was missing or wrong.
     * See issue #1816.
     */
    private static function fixEncoding(mixed $value): mixed
    {
        if (!is_string($value) || $value === '' || mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        foreach (self::FALLBACK_ENCODINGS as $encoding) {
            if (mb_check_encoding($value, $encoding)) {
                return mb_convert_encoding($value, 'UTF-8', $encoding);
            }
        }

        return $value;
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
            'file_size' => $this->fileSize,
        ];
    }
}
