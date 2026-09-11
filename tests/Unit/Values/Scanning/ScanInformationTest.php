<?php

namespace Tests\Unit\Values\Scanning;

use App\Values\Scanning\ScanInformation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class ScanInformationTest extends TestCase
{
    /** @param array<mixed> $id3v2 */
    private static function scanId3v2(array $id3v2): ScanInformation
    {
        return ScanInformation::fromGetId3Info([
            'tags' => ['id3v2' => $id3v2['tags'] ?? []],
            'id3v2' => $id3v2['frames'] ?? [],
        ], test_path('songs/full.mp3'));
    }

    #[Test]
    public function getMusicBrainzIdsFromId3v2TextFrames(): void
    {
        $info = self::scanId3v2([
            'tags' => [
                'text' => [
                    'MusicBrainz Album Id' => '66666666-6666-6666-6666-666666666666',
                    'MusicBrainz Artist Id' => '77777777-7777-7777-7777-777777777777',
                    'MusicBrainz Album Artist Id' => '88888888-8888-8888-8888-888888888888',
                ],
            ],
        ]);

        self::assertSame('66666666-6666-6666-6666-666666666666', $info->albumMbid);
        self::assertSame('77777777-7777-7777-7777-777777777777', $info->artistMbid);
        self::assertSame('88888888-8888-8888-8888-888888888888', $info->albumArtistMbid);
    }

    #[Test]
    public function getRecordingMbidFromMusicBrainzUfidFrame(): void
    {
        $info = self::scanId3v2([
            'frames' => [
                'UFID' => [
                    ['ownerid' => 'http://www.cddb.com/id3/taginfo1.html', 'data' => 'not-a-mbid'],
                    ['ownerid' => 'http://musicbrainz.org', 'data' => '99999999-9999-9999-9999-999999999999'],
                ],
            ],
        ]);

        self::assertSame('99999999-9999-9999-9999-999999999999', $info->mbid);
    }

    #[Test]
    public function ignoreUfidFramesFromOtherOwners(): void
    {
        $info = self::scanId3v2([
            'frames' => [
                'UFID' => [['ownerid' => 'http://www.cddb.com/id3/taginfo1.html', 'data' => 'not-a-mbid']],
            ],
        ]);

        self::assertNull($info->mbid);
    }

    #[Test]
    public function releaseTrackIdIsNotTreatedAsTheRecordingId(): void
    {
        // A release track and the recording it points at are different entities, and only the latter is
        // what a listen should be attributed to.
        $info = self::scanId3v2([
            'tags' => ['text' => ['MusicBrainz Release Track Id' => '55555555-5555-5555-5555-555555555555']],
        ]);

        self::assertNull($info->mbid);
    }
}
