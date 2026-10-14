<?php

namespace Tests\Integration\Builders;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\SongAnalysis;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;
use Tests\TestCase;

use function Tests\create_user;

class SongBuilderTest extends TestCase
{
    #[Test]
    public function sortingByAlbumKeepsTheTrackOrderOfACompilation(): void
    {
        $compilation = Album::factory()->for(Artist::factory()->createOne([
            'name' => Artist::VARIOUS_NAME,
        ]))->createOne(['name' => 'Hits']);

        $first = Song::factory()->for($compilation)->createOne([
            'album_name' => 'Hits',
            'artist_name' => 'Zappa',
            'disc' => 1,
            'track' => 1,
        ]);

        $second = Song::factory()->for($compilation)->createOne([
            'album_name' => 'Hits',
            'artist_name' => 'ABBA',
            'disc' => 1,
            'track' => 2,
        ]);

        $sortedIds = Song::query()->sort(['album_name'], 'asc')->pluck('songs.id')->all();

        self::assertSame([$first->id, $second->id], $sortedIds);
    }

    #[Test]
    public function sortingByTrackGoesByDiscFirst(): void
    {
        $discTwoTrackOne = Song::factory()->createOne(['disc' => 2, 'track' => 1]);
        $discOneTrackTwo = Song::factory()->createOne(['disc' => 1, 'track' => 2]);

        $sortedIds = Song::query()->sort(['track'], 'asc')->pluck('songs.id')->all();

        self::assertSame([$discOneTrackTwo->id, $discTwoTrackOne->id], $sortedIds);
    }

    #[Test]
    public function loadTheAnalysisWithoutWaveformInUserContextWithPlus(): void
    {
        PlusTestCase::enablePlusLicense();

        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->createOne();
        SongAnalysis::factory()->for($song)->createOne(['loudness' => -9.5, 'true_peak' => 0.8]);

        $loadedSong = Song::query(user: $user)->withUserContext()->findOrFail($song->id);

        self::assertTrue($loadedSong->relationLoaded('analysis'));
        self::assertSame(-9.5, $loadedSong->analysis->loudness);
        self::assertSame(0.8, $loadedSong->analysis->true_peak);
        self::assertArrayNotHasKey('waveform', $loadedSong->analysis->getAttributes());
    }

    #[Test]
    public function skipTheAnalysisInUserContextWithoutPlus(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->createOne();
        SongAnalysis::factory()->for($song)->createOne();

        $loadedSong = Song::query(user: $user)->withUserContext()->findOrFail($song->id);

        self::assertFalse($loadedSong->relationLoaded('analysis'));
    }
}
