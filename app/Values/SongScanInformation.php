<?php

namespace App\Values;

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use SplFileInfo;
use Throwable;

final class SongScanInformation implements Arrayable
{
    private function __construct(
        public ?string $title,
        public ?string $albumName,
        public ?string $artistName,
        public ?string $albumArtistName,
        public ?int $track,
        public ?int $disc,
        public ?string $lyrics,
        public ?int $length,
        public ?array $cover,
        public ?string $path,
        public ?int $mTime,
    ) {
    }

    public static function fromGetId3Info(array $info): self
    {
        // We prefer ID3v2 tags over ID3v1 tags.
        $tags = array_merge(Arr::get($info, 'tags.id3v1', []), Arr::get($info, 'tags.id3v2', []));
        $comments = Arr::get($info, 'comments', []);

        $title = self::getTag($tags, 'title');
        $albumName = self::getTag($tags, 'album', Album::UNKNOWN_NAME);
        $artistName = self::getTag($tags, 'artist', Artist::UNKNOWN_NAME);
        $albumArtistName = self::getTag($tags, ['albumartist', 'album_artist', 'band']);

        // If the song is explicitly marked as a compilation but there's no album artist name, use the umbrella
        // "Various Artists" artist.
        if (self::getTag($tags, 'part_of_a_compilation') && !$albumArtistName) {
            $albumArtistName = Artist::VARIOUS_NAME;
        }

        $track = (int) self::getTag($tags, ['track', 'tracknumber', 'track_number']);
        $disc = (int) self::getTag($tags, 'part_of_a_set', 1);
        $lyrics = self::getTag($tags, ['unsynchronised_lyric', 'unsychronised_lyric']);
        $path = Arr::get($info, 'filenamepath');
        $length = (float) Arr::get($info, 'playtime_seconds');
        $cover = self::getTag($comments, 'picture', []);
        $mTime = self::getMTime($path);

        return new self(
            title: $title,
            albumName: $albumName,
            artistName: $artistName,
            albumArtistName: $albumArtistName,
            track: $track,
            disc: $disc,
            lyrics: $lyrics,
            length: $length,
            cover: $cover,
            path: $path,
            mTime: $mTime,
        );
    }

    private static function getTag(array $arr, string | array $keys, $default = ''): mixed
    {
        $keys = Arr::wrap($keys);

        for ($i = 0; $i < count($keys); ++$i) {
            $value = Arr::get($arr, $keys[$i] . '.0');

            if ($value) {
                break;
            }
        }

        return $value ?? $default;
    }

    private static function getMTime(mixed $path): int
    {
        $splFileInfo = new SplFileInfo($path);

        // Workaround for #344, where getMTime() fails for certain files with Unicode names on Windows.
        try {
            return $splFileInfo->getMTime();
        } catch (Throwable) {
            // Just use current stamp for mtime.
            return time();
        }
    }

    /** @return array<mixed> */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'album' => $this->albumName,
            'artist' => $this->artistName,
            'albumartist' => $this->albumArtistName,
            'track' => $this->track,
            'disc' => $this->disc,
            'lyrics' => $this->lyrics,
            'length' => $this->length,
            'cover' => $this->cover,
            'path' => $this->path,
            'mtime' => $this->mTime,
        ];
    }
}
