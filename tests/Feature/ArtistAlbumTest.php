<?php

namespace Tests\Feature;

use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtistAlbumTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        $artist = Artist::factory()->createOne();
        Album::factory()->for($artist)->createMany(5);

        $this->getAs("api/artists/{$artist->id}/albums")->assertJsonStructure([0 => AlbumResource::JSON_STRUCTURE]);
    }

    #[Test]
    public function indexIncludesAlbumsTheArtistAppearsOn(): void
    {
        $artist = Artist::factory()->createOne();
        $ownAlbum = Album::factory()->for($artist)->createOne();
        $appearedOnAlbum = Album::factory()->createOne();

        Song::factory()->for($appearedOnAlbum)->for($artist)->createOne();
        Album::factory()->createOne();

        $albumIds = $this->getAs("api/artists/{$artist->id}/albums")->assertOk()->json('*.id');

        self::assertEqualsCanonicalizing([$ownAlbum->id, $appearedOnAlbum->id], $albumIds);
    }
}
