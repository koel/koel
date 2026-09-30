<?php

namespace Tests\Integration\Services\Scanners;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\Scanners\ScanIndexer;
use App\Values\Scanning\ScanResult;
use App\Values\Scanning\ScanResultCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
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
        $saved = Song::factory()->createOne();
        $saved->syncGenres('Rock');

        $this->engine->updated = [];

        app(ScanIndexer::class)->reindex(ScanResultCollection::create()->add(ScanResult::success($saved->path))->add(ScanResult::error(
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
        $song = Song::factory()->createOne();
        $this->engine->updated = [];

        app(ScanIndexer::class)->reindex(ScanResultCollection::create()->add(ScanResult::error(
            $song->path,
            'Genre sync failed',
        )));

        self::assertSame([(string) $song->id], $this->engine->updatedKeysOf(Song::class));
    }

    #[Test]
    public function indexesTheAlbumArtistWhenItIsNotTheTrackArtist(): void
    {
        $albumArtist = Artist::factory()->createOne();
        $trackArtist = Artist::factory()->createOne();
        $album = Album::factory()->for($albumArtist, 'artist')->createOne();
        $song = Song::factory()->for($album)->for($trackArtist, 'artist')->createOne();
        $this->engine->updated = [];

        app(ScanIndexer::class)->reindex(ScanResultCollection::create()->add(ScanResult::success($song->path)));

        self::assertContains((string) $albumArtist->id, $this->engine->updatedKeysOf(Artist::class));
        self::assertContains((string) $trackArtist->id, $this->engine->updatedKeysOf(Artist::class));
    }

    #[Test]
    public function indexesAWholeChunkWithoutAQueryPerSong(): void
    {
        [$queriesForOne] = $this->indexNewSongs(1);
        [$queriesForFive, $songs] = $this->indexNewSongs(5);

        self::assertSame($queriesForOne, $queriesForFive);
        self::assertSame($this->countQueries(static fn () => app(SongRepository::class)->getManyByPaths(
            $songs->pluck('path')->all(),
        )), $queriesForFive);
        self::assertEqualsCanonicalizing(
            $songs
                ->pluck('artist_id')
                ->map(strval(...))
                ->all(),
            $this->engine->updatedKeysOf(Artist::class),
        );
        self::assertSame(
            [(string) Genre::query()->where('name', 'Rock')->value('id')],
            $this->engine->updatedKeysOf(Genre::class),
        );
    }

    #[Test]
    public function aFailingChunkIsReportedAndDoesNotAbort(): void
    {
        Log::spy();
        $song = Song::factory()->createOne();
        $this->useSearchEngine('tntsearch', new class extends NullEngine {
            public function update(mixed $models): void
            {
                throw new RuntimeException('database is locked');
            }
        });

        app(ScanIndexer::class)->reindex(ScanResultCollection::create()->add(ScanResult::success($song->path)));

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

        app(ScanIndexer::class)->reindex(ScanResultCollection::create()->add(ScanResult::skipped('/media/foo.mp3')));

        self::assertSame([], $this->engine->updated);
    }

    #[Test]
    public function indexesNothingWithOtherSearchDrivers(): void
    {
        $engine = new RecordingSearchEngine();
        $this->useSearchEngine('meilisearch', $engine);
        $song = Song::factory()->createOne();
        $engine->updated = [];

        app(ScanIndexer::class)->reindex(ScanResultCollection::create()->add(ScanResult::success($song->path)));

        self::assertSame([], $engine->updated);
    }

    /** @return array{int, Collection<int, Song>} */
    private function indexNewSongs(int $count): array
    {
        $songs = Song::factory()->createMany($count);
        $songs->each(static fn (Song $song) => $song->syncGenres('Rock'));

        $results = ScanResultCollection::create();
        $songs->each(static fn (Song $song) => $results->add(ScanResult::success($song->path)));

        $indexer = app(ScanIndexer::class);
        $this->engine->updated = [];

        return [$this->countQueries(static fn () => $indexer->reindex($results)), $songs];
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
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
