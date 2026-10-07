<?php

namespace Tests\Feature\KoelPlus\Subsonic;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class GetArtistsTest extends PlusTestCase
{
    #[Test]
    public function albumCountOfASharedArtistLeavesOutAlbumsTheViewerCannotOpen(): void
    {
        $owner = create_user();
        $artist = Artist::factory()->for($owner)->createOne();

        $sharedAlbum = Album::factory()->for($artist)->for($owner)->createOne();
        Song::factory()
            ->for($sharedAlbum)
            ->for($artist)
            ->for($owner, 'owner')
            ->public()
            ->createOne();

        $privateAlbum = Album::factory()->for($artist)->for($owner)->createOne();
        Song::factory()
            ->for($privateAlbum)
            ->for($artist)
            ->for($owner, 'owner')
            ->private()
            ->createOne();

        $viewer = create_user();
        $viewer->preferences->includePublicMedia = true;
        $viewer->save();

        $response = $this->getJson("/rest/getArtists.view?apiKey={$viewer->subsonic_api_key}&f=json")->assertOk();

        $listedArtist = collect($response->json('subsonic-response.artists.index'))
            ->flatMap(static fn (array $index) => $index['artist'])
            ->firstWhere('id', $artist->id);

        self::assertSame(1, $listedArtist['albumCount']);
    }
}
