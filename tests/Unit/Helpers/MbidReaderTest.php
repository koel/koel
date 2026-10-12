<?php

namespace Tests\Unit\Helpers;

use App\Helpers\MbidReader;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MbidReaderTest extends TestCase
{
    #[Test]
    public function readIdentifiersFromPlainTags(): void
    {
        $tags = [
            'musicbrainz_trackid' => ['11111111-1111-1111-1111-111111111111'],
            'musicbrainz_albumid' => ['22222222-2222-2222-2222-222222222222'],
            'musicbrainz_artistid' => ['33333333-3333-3333-3333-333333333333'],
            'musicbrainz_albumartistid' => ['44444444-4444-4444-4444-444444444444'],
        ];

        self::assertSame('11111111-1111-1111-1111-111111111111', MbidReader::getRecordingMbid([], $tags));
        self::assertSame('22222222-2222-2222-2222-222222222222', MbidReader::getAlbumMbid($tags));
        self::assertSame('33333333-3333-3333-3333-333333333333', MbidReader::getArtistMbid($tags));
        self::assertSame('44444444-4444-4444-4444-444444444444', MbidReader::getAlbumArtistMbid($tags));
    }

    #[Test]
    public function readIdentifiersFromId3v2TextFrames(): void
    {
        $tags = [
            'text' => [
                'MusicBrainz Album Id' => '66666666-6666-6666-6666-666666666666',
                'MusicBrainz Artist Id' => '77777777-7777-7777-7777-777777777777',
                'MusicBrainz Album Artist Id' => '88888888-8888-8888-8888-888888888888',
            ],
        ];

        self::assertSame('66666666-6666-6666-6666-666666666666', MbidReader::getAlbumMbid($tags));
        self::assertSame('77777777-7777-7777-7777-777777777777', MbidReader::getArtistMbid($tags));
        self::assertSame('88888888-8888-8888-8888-888888888888', MbidReader::getAlbumArtistMbid($tags));
    }

    #[Test]
    public function readRecordingIdentifierFromMusicBrainzUfidFrame(): void
    {
        $info = [
            'id3v2' => [
                'UFID' => [
                    ['ownerid' => 'http://www.cddb.com/id3/taginfo1.html', 'data' => 'not-an-mbid'],
                    ['ownerid' => 'http://musicbrainz.org', 'data' => '99999999-9999-9999-9999-999999999999'],
                ],
            ],
        ];

        self::assertSame('99999999-9999-9999-9999-999999999999', MbidReader::getRecordingMbid($info, []));
    }

    #[Test]
    public function ignoreUfidFramesFromOtherOwners(): void
    {
        $info = [
            'id3v2' => ['UFID' => [['ownerid' => 'http://www.cddb.com/id3/taginfo1.html', 'data' => 'not-an-mbid']]],
        ];

        self::assertNull(MbidReader::getRecordingMbid($info, []));
    }

    #[Test]
    public function releaseTrackIdIsNotTreatedAsTheRecordingId(): void
    {
        // A release track and the recording it points at are different entities, and only the latter is
        // what a listen should be attributed to.
        $tags = ['text' => ['MusicBrainz Release Track Id' => '55555555-5555-5555-5555-555555555555']];

        self::assertNull(MbidReader::getRecordingMbid([], $tags));
    }

    #[Test]
    public function readNothingFromAnUntaggedFile(): void
    {
        self::assertNull(MbidReader::getRecordingMbid([], []));
        self::assertNull(MbidReader::getAlbumMbid([]));
        self::assertNull(MbidReader::getArtistMbid([]));
        self::assertNull(MbidReader::getAlbumArtistMbid([]));
    }
}
