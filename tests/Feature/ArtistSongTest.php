<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtistSongTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        $artist = Artist::factory()->createOne();

        Song::factory()->for($artist)->createMany(5);

        $this->getAs("api/artists/{$artist->id}/songs")->assertJsonStructure([0 => SongResource::JSON_STRUCTURE]);
    }

    #[Test]
    public function indexIncludesSongsPerformedByOthersOnTheArtistsAlbums(): void
    {
        $artist = Artist::factory()->createOne();
        $performedSong = Song::factory()->for($artist)->createOne();
        $songOnArtistsAlbum = Song::factory()->for(Album::factory()->for($artist)->createOne())->createOne();

        Song::factory()->createOne();

        $songIds = $this->getAs("api/artists/{$artist->id}/songs")->assertOk()->json('*.id');

        self::assertEqualsCanonicalizing([$performedSong->id, $songOnArtistsAlbum->id], $songIds);
    }
}
