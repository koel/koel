<?php

namespace App\Values\Scanning;

use App\Helpers\Encoding\TagFixer;
use App\Helpers\SyncedLyricsConverter;
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
        public ?string $mbid,
        public ?string $albumMbid,
        public ?string $artistMbid,
        public ?string $albumArtistMbid,
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

        $albumArtistName = TagFixer::fix(self::getTag($tags, ['albumartist', 'album_artist', 'band']));

        // If the song is explicitly marked as a compilation but there's no album artist name, use the umbrella
        // "Various Artists" artist.
        if (!$albumArtistName && self::getTag($tags, 'part_of_a_compilation')) {
            $albumArtistName = Artist::VARIOUS_NAME;
        }

        $cover = [self::getTag($comments, 'cover', null)];

        if ($cover[0] === null) {
            $cover = self::getTag($comments, 'picture', []);
        }

        $syncedLyrics = SyncedLyricsConverter::fromSyltFrames(Arr::wrap(Arr::get($info, 'id3v2.SYLT', [])));

        $lyrics = $syncedLyrics ?: html_entity_decode(TagFixer::fix(self::getTag($tags, [
            'unsynchronised_lyric',
            'unsychronised_lyric',
            'unsyncedlyrics',
            'lyrics',
        ])));

        return new self(
            title: html_entity_decode(TagFixer::fix(self::getTag($tags, 'title', pathinfo($path, PATHINFO_FILENAME)))),
            albumName: html_entity_decode(TagFixer::fix(self::getTag($tags, 'album', Album::UNKNOWN_NAME))),
            artistName: html_entity_decode(TagFixer::fix(self::getTag($tags, 'artist', Artist::UNKNOWN_NAME))),
            albumArtistName: html_entity_decode($albumArtistName),
            mbid: self::getRecordingMbid($info, $tags),
            albumMbid: self::getMusicBrainzId($tags, 'musicbrainz_albumid', 'MusicBrainz Album Id'),
            artistMbid: self::getMusicBrainzId($tags, 'musicbrainz_artistid', 'MusicBrainz Artist Id'),
            albumArtistMbid: self::getMusicBrainzId($tags, 'musicbrainz_albumartistid', 'MusicBrainz Album Artist Id'),
            track: (int) self::getTag($tags, ['track', 'tracknumber', 'track_number']),
            disc: (int) self::getTag($tags, ['discnumber', 'part_of_a_set'], 1),
            year: (int) self::getTag($tags, ['year', 'date']) ?: null,
            genre: TagFixer::fix(self::getTag($tags, 'genre')),
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
        ?string $mbid = null,
        ?string $albumMbid = null,
        ?string $artistMbid = null,
        ?string $albumArtistMbid = null,
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
            mbid: $mbid,
            albumMbid: $albumMbid,
            artistMbid: $artistMbid,
            albumArtistMbid: $albumArtistMbid,
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

    /**
     * Vorbis comments expose MusicBrainz identifiers as ordinary tags, whereas ID3v2 buries them in TXXX frames
     * that getID3 keys by their original description.
     */
    private static function getMusicBrainzId(array $tags, string $vorbisKey, string $txxxDescription): ?string
    {
        return self::getTag($tags, $vorbisKey, null) ?: Arr::get($tags, "text.$txxxDescription") ?: null;
    }

    /**
     * ID3v2 stores the recording identifier in a UFID frame owned by MusicBrainz — its
     * "MusicBrainz Release Track Id" TXXX frame identifies the release track instead, which is a different thing.
     */
    private static function getRecordingMbid(array $info, array $tags): ?string
    {
        $fromVorbisComments = self::getTag($tags, 'musicbrainz_trackid', null);

        if ($fromVorbisComments) {
            return $fromVorbisComments;
        }

        foreach (Arr::get($info, 'id3v2.UFID', []) as $frame) {
            if (Arr::get($frame, 'ownerid') === 'http://musicbrainz.org') {
                return Arr::get($frame, 'data') ?: null;
            }
        }

        return null;
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
            'mbid' => $this->mbid,
            'album_mbid' => $this->albumMbid,
            'artist_mbid' => $this->artistMbid,
            'albumartist_mbid' => $this->albumArtistMbid,
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
