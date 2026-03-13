<?php

namespace Tests\Feature\Commands;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PruneLibraryCommandTest extends TestCase
{
    #[Test]
    public function pruneEmptyArtistsAndAlbums(): void
    {
        $emptyAlbum = Album::factory()->createOne();
        $emptyArtist = Artist::factory()->createOne();

        $albumWithSongs = Album::factory()->createOne();
        Song::factory()->for($albumWithSongs)->createOne();

        $this->artisan('koel:prune')->assertSuccessful();

        self::assertModelMissing($emptyAlbum);
        self::assertModelMissing($emptyArtist);
        self::assertModelExists($albumWithSongs);
    }

    #[Test]
    public function dryRunWithoutDeleting(): void
    {
        $emptyAlbum = Album::factory()->createOne();
        $emptyArtist = Artist::factory()->createOne();

        $this
            ->artisan('koel:prune', ['--dry-run' => true])
            ->expectsOutput('Dry run: no changes made.')
            ->assertSuccessful();

        self::assertModelExists($emptyAlbum);
        self::assertModelExists($emptyArtist);
    }
}
