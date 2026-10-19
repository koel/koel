<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\PlusTestCase;

use function Tests\create_user;

class PrivateLibraryAccessTest extends PlusTestCase
{
    #[Test]
    #[TestWith(['information'])]
    #[TestWith(['thumbnail'])]
    public function refuseSomeoneElsesAlbum(string $endpoint): void
    {
        $album = Album::factory()->createOne();

        $this->getAs("api/albums/{$album->id}/{$endpoint}", create_user())->assertForbidden();
    }

    #[Test]
    #[TestWith(['information'])]
    #[TestWith(['events'])]
    public function refuseSomeoneElsesArtist(string $endpoint): void
    {
        $artist = Artist::factory()->createOne();

        $this->getAs("api/artists/{$artist->id}/{$endpoint}", create_user())->assertForbidden();
    }

    #[Test]
    public function refuseToScrobbleSomeoneElsesPrivateSong(): void
    {
        $song = Song::factory()->private()->createOne();

        $this->postAs("api/songs/{$song->id}/scrobble", ['timestamp' => time()], create_user())->assertForbidden();
    }
}
