<?php

namespace Tests\Feature\Subsonic;

use App\Http\Responses\Subsonic\Resources\AlbumResource;
use App\Http\Responses\Subsonic\Resources\ArtistResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetArtistTest extends TestCase
{
    #[Test]
    public function returnsArtistWithAlbums(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne(['name' => 'Tool', 'user_id' => $user->id]);

        Album::factory()->createMany([
            ['name' => 'Ænima', 'artist_id' => $artist->id, 'artist_name' => $artist->name, 'user_id' => $user->id],
            ['name' => 'Lateralus', 'artist_id' => $artist->id, 'artist_name' => $artist->name, 'user_id' => $user->id],
        ]);

        $response = $this
            ->getJson("/rest/getArtist.view?apiKey={$user->subsonic_api_key}&f=json&id={$artist->id}")
            ->assertOk()
            ->assertJsonStructure([
                'subsonic-response' => [
                    'artist' => array_merge(ArtistResource::JSON_STRUCTURE, [
                        'album' => ['*' => AlbumResource::JSON_STRUCTURE],
                    ]),
                ],
            ])
            ->assertJsonPath('subsonic-response.artist.id', $artist->id)
            ->assertJsonPath('subsonic-response.artist.name', 'Tool');

        $albumNames = collect($response->json('subsonic-response.artist.album'))->pluck('name')->all();
        self::assertContains('Ænima', $albumNames);
        self::assertContains('Lateralus', $albumNames);
    }

    #[Test]
    public function givesAnArtistWithoutAnImageCoverArtFromTheirAlbums(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne(['image' => '', 'user_id' => $user->id]);
        Album::factory()->for($artist)->createOne(['cover' => 'cover.webp', 'user_id' => $user->id]);

        $this->getJson("/rest/getArtist.view?apiKey={$user->subsonic_api_key}&f=json&id={$artist->id}")->assertJsonPath(
            'subsonic-response.artist.coverArt',
            $artist->id,
        );
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getArtist.view?apiKey={$user->subsonic_api_key}&f=json&id=does-not-exist")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);
    }

    #[Test]
    public function missingIdReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getArtist.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function albumCountIncludesCompilationsTheArtistAppearsOn(): void
    {
        $artist = self::createArtistWithOwnAlbumAndACompilationAppearance();

        $this
            ->getJson("/rest/getArtist.view?apiKey={$artist->user->subsonic_api_key}&f=json&id={$artist->id}")
            ->assertOk()
            ->assertJsonCount(2, 'subsonic-response.artist.album')
            ->assertJsonPath('subsonic-response.artist.albumCount', 2);
    }

    private static function createArtistWithOwnAlbumAndACompilationAppearance(): Artist
    {
        $artist = Artist::factory()->createOne(['name' => 'Tool']);
        Album::factory()->for($artist)->createOne();

        $compilation = Album::factory()->for(Artist::factory()->createOne([
            'name' => Artist::VARIOUS_NAME,
        ]))->createOne();

        Song::factory()->for($compilation)->for($artist)->createOne();

        return $artist;
    }
}
