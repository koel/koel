<?php

namespace Tests\Integration\Services\Scanners;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use App\Services\Scanners\ScanIndexer;
use App\Values\Scanning\ScanResult;
use App\Values\Scanning\ScanResultCollection;
use Illuminate\Support\Facades\Log;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\Engine;
use Laravel\Scout\Engines\NullEngine;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Fakes\RecordingSearchEngine;
use Tests\TestCase;

class ScanIndexerTest extends TestCase
{
    private RecordingSearchEngine $engine;

    public function setUp(): void
    {
        parent::setUp();

        $this->engine = new RecordingSearchEngine();
        $this->useSearchEngine('tntsearch', $this->engine);
    }

    #[Test]
    public function indexesTheSavedSongsAndWhatTheyBelongTo(): void
    {
        /** @var Song $saved */
        $saved = Song::factory()->create();
        $saved->syncGenres('Rock');

        $this->engine->updated = [];

        (new ScanIndexer())->reindex(ScanResultCollection::create()->add(ScanResult::success($saved->path))->add(ScanResult::error(
            '/media/unreadable.mp3',
            'Unsupported file',
        ))->add(ScanResult::skipped('/media/untouched.mp3')));

        self::assertSame([(string) $saved->id], $this->engine->updatedKeysOf(Song::class));
        self::assertSame([(string) $saved->album_id], $this->engine->updatedKeysOf(Album::class));
        self::assertSame([(string) $saved->artist_id], $this->engine->updatedKeysOf(Artist::class));
        self::assertSame(
            Genre::query()
                ->where('name', 'Rock')
                ->pluck('id')
                ->map(strval(...))
                ->all(),
            $this->engine->updatedKeysOf(Genre::class),
        );
    }

    #[Test]
    public function indexesASongThatWasSavedBeforeItsScanFailed(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create();
        $this->engine->updated = [];

        (new ScanIndexer())->reindex(ScanResultCollection::create()->add(ScanResult::error(
            $song->path,
            'Genre sync failed',
        )));

        self::assertSame([(string) $song->id], $this->engine->updatedKeysOf(Song::class));
    }

    #[Test]
    public function indexesTheAlbumArtistWhenItIsNotTheTrackArtist(): void
    {
        /** @var Artist $albumArtist */
        $albumArtist = Artist::factory()->create();
        /** @var Album $album */
        $album = Album::factory()->for($albumArtist)->create();
        /** @var Song $song */
        $song = Song::factory()->for($album)->create(['artist_id' => Artist::factory()->create()->id]);
        $this->engine->updated = [];

        (new ScanIndexer())->reindex(ScanResultCollection::create()->add(ScanResult::success($song->path)));

        self::assertContains((string) $albumArtist->id, $this->engine->updatedKeysOf(Artist::class));
        self::assertContains((string) $song->artist_id, $this->engine->updatedKeysOf(Artist::class));
    }

    #[Test]
    public function aFailingChunkIsReportedAndDoesNotAbort(): void
    {
        Log::spy();
        /** @var Song $song */
        $song = Song::factory()->create();
        $this->useSearchEngine('tntsearch', new class extends NullEngine {
            public function update(mixed $models): void
            {
                throw new RuntimeException('database is locked');
            }
        });

        (new ScanIndexer())->reindex(ScanResultCollection::create()->add(ScanResult::success($song->path)));

        Log::shouldHaveReceived('warning') // @phpstan-ignore-line
            ->once()
            ->withArgs(
                static fn (string $message) => (
                    str_contains($message, 'database is locked') && str_contains($message, $song->path)
                ),
            );
    }

    #[Test]
    public function indexesNothingForAScanThatSavedNothing(): void
    {
        $this->engine->updated = [];

        (new ScanIndexer())->reindex(ScanResultCollection::create()->add(ScanResult::skipped('/media/foo.mp3')));

        self::assertSame([], $this->engine->updated);
    }

    #[Test]
    public function indexesNothingWithOtherSearchDrivers(): void
    {
        $engine = new RecordingSearchEngine();
        $this->useSearchEngine('meilisearch', $engine);
        /** @var Song $song */
        $song = Song::factory()->create();
        $engine->updated = [];

        (new ScanIndexer())->reindex(ScanResultCollection::create()->add(ScanResult::success($song->path)));

        self::assertSame([], $engine->updated);
    }

    private function useSearchEngine(string $driver, Engine $engine): void
    {
        $manager = $this->app->make(EngineManager::class);
        // @mago-ignore lint:prefer-static-closure
        $manager->extend($driver, fn () => $engine);
        $manager->forgetDrivers();
        config(['scout.driver' => $driver]);
    }
}
