<?php

namespace Tests\Unit\Values\Album;

use App\Values\Album\AlbumInformation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class AlbumInformationTest extends TestCase
{
    /** @return array<mixed> */
    private static function getFixtureTracks(): array
    {
        $tracks = [];

        foreach (Arr::get(File::json(test_path('fixtures/musicbrainz/recordings.json')), 'media', []) as $media) {
            array_push($tracks, ...Arr::get($media, 'tracks', []));
        }

        return $tracks;
    }

    #[Test]
    public function linkTracksToTheirRecording(): void
    {
        $tracks = self::getFixtureTracks();
        $first = $tracks[0];

        self::assertNotSame(Arr::get($first, 'id'), Arr::get($first, 'recording.id'));

        $information = AlbumInformation::make()->withMusicBrainzTracks($tracks);

        self::assertSame(
            'https://musicbrainz.org/recording/' . Arr::get($first, 'recording.id'),
            $information->tracks[0]['url'],
        );
    }

    #[Test]
    public function mapTitleAndLength(): void
    {
        $information = AlbumInformation::make()->withMusicBrainzTracks([
            ['id' => 'track-mbid', 'title' => 'Make It', 'length' => 195_000, 'recording' => ['id' => 'rec-mbid']],
        ]);

        self::assertSame('Make It', $information->tracks[0]['title']);
        self::assertSame(195, $information->tracks[0]['length']);
    }

    #[Test]
    public function omitTheLinkWhenTheTrackHasNoRecording(): void
    {
        $information = AlbumInformation::make()->withMusicBrainzTracks([
            ['id' => 'track-mbid', 'title' => 'Make It', 'length' => 195_000],
        ]);

        self::assertNull($information->tracks[0]['url']);
    }
}
