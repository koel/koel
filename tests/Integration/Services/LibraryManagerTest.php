<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Services\LibraryManager;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\Engine;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class LibraryManagerTest extends TestCase
{
    private LibraryManager $libraryManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->libraryManager = app(LibraryManager::class);
    }

    #[Test]
    public function handOverScannedSongsAndTheirArtistAndAlbumToAnotherAdmin(): void
    {
        $leavingAdmin = create_admin();
        $heir = create_admin();
        $artist = Artist::factory()->for($leavingAdmin)->createOne();
        $album = Album::factory()->for($leavingAdmin)->for($artist)->createOne();
        $scannedSong = Song::factory()
            ->for($leavingAdmin, 'owner')
            ->for($artist)
            ->for($album)
            ->createOne(['path' => '/music/Scanned.mp3']);
        $uploadedSong = Song::factory()->for($leavingAdmin, 'owner')->createOne([
            'path' => '/music/__KOEL_UPLOADS_$1__/Uploaded.mp3',
        ]);

        $this->libraryManager->handOverScannedLibraryOfUser($leavingAdmin);

        self::assertTrue($scannedSong->refresh()->owner->is($heir));
        self::assertTrue($artist->refresh()->user->is($heir));
        self::assertTrue($album->refresh()->user->is($heir));
        self::assertTrue($uploadedSong->refresh()->owner->is($leavingAdmin));
    }

    #[Test]
    public function prunesEmptyAlbumsAndArtists(): void
    {
        $emptyAlbum = Album::factory()->createOne();
        $emptyArtist = Artist::factory()->createOne();

        $albumWithSongs = Album::factory()->createOne();
        Song::factory()->for($albumWithSongs)->createOne();

        $this->libraryManager->prune();

        self::assertModelMissing($emptyAlbum);
        self::assertModelMissing($emptyArtist);
        self::assertModelExists($albumWithSongs);
    }

    #[Test]
    public function dryRunDoesNotDelete(): void
    {
        $emptyAlbum = Album::factory()->createOne();
        $emptyArtist = Artist::factory()->createOne();

        $this->libraryManager->prune(dryRun: true);

        self::assertModelExists($emptyAlbum);
        self::assertModelExists($emptyArtist);
    }

    #[Test]
    public function flushesEmptyAlbumsAndArtistsFromSearchIndex(): void
    {
        $engine = Mockery::spy(Engine::class);
        $manager = Mockery::mock(EngineManager::class);
        $manager->shouldReceive('engine')->andReturn($engine);
        $this->app->instance(EngineManager::class, $manager);

        Album::factory()->createOne();
        Artist::factory()->createOne();

        $this->libraryManager->prune();

        $engine->shouldHaveReceived('delete')->twice(); // @phpstan-ignore-line
    }

    #[Test]
    public function dryRunDoesNotTouchSearchIndex(): void
    {
        $engine = Mockery::spy(Engine::class);
        $manager = Mockery::mock(EngineManager::class);
        $manager->shouldReceive('engine')->andReturn($engine);
        $this->app->instance(EngineManager::class, $manager);

        Album::factory()->createOne();

        $this->libraryManager->prune(dryRun: true);

        $engine->shouldNotHaveReceived('delete');
    }
}
