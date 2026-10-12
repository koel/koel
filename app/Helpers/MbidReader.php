<?php

namespace App\Helpers;

use Illuminate\Support\Arr;

/**
 * Reads MusicBrainz identifiers out of a getID3 tag set.
 *
 * Where an identifier lives depends on the container: Vorbis comments expose them as plain tags, whereas ID3v2
 * keeps them in TXXX frames keyed by their original description, and the recording identifier in a UFID frame.
 */
class MbidReader
{
    private const string MUSICBRAINZ_UFID_OWNER = 'http://musicbrainz.org';

    /**
     * @param array<mixed> $info The full getID3 analysis
     * @param array<mixed> $tags The merged tag set
     */
    public static function getRecordingMbid(array $info, array $tags): ?string
    {
        return self::read($tags, 'musicbrainz_trackid') ?: self::getMbidFromUfidFrames($info);
    }

    /** @param array<mixed> $tags */
    public static function getAlbumMbid(array $tags): ?string
    {
        return self::read($tags, 'musicbrainz_albumid', 'MusicBrainz Album Id');
    }

    /** @param array<mixed> $tags */
    public static function getArtistMbid(array $tags): ?string
    {
        return self::read($tags, 'musicbrainz_artistid', 'MusicBrainz Artist Id');
    }

    /** @param array<mixed> $tags */
    public static function getAlbumArtistMbid(array $tags): ?string
    {
        return self::read($tags, 'musicbrainz_albumartistid', 'MusicBrainz Album Artist Id');
    }

    /**
     * A "MusicBrainz Release Track Id" TXXX frame identifies the release track rather than the recording,
     * so only the UFID frame answers for the recording.
     *
     * @param array<mixed> $info
     */
    private static function getMbidFromUfidFrames(array $info): ?string
    {
        foreach (Arr::get($info, 'id3v2.UFID', []) as $frame) {
            if (Arr::get($frame, 'ownerid') === self::MUSICBRAINZ_UFID_OWNER) {
                return Arr::get($frame, 'data') ?: null;
            }
        }

        return null;
    }

    /** @param array<mixed> $tags */
    private static function read(array $tags, string $tagKey, ?string $txxxDescription = null): ?string
    {
        return Arr::get($tags, "$tagKey.0") ?: Arr::get($tags, "text.$txxxDescription") ?: null;
    }
}
