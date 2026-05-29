<?php

namespace Tests\Feature\Subsonic;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class Search3Test extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config()->set('scout.driver', 'collection');
    }

    protected function tearDown(): void
    {
        config()->set('scout.driver', null);

        parent::tearDown();
    }

    #[Test]
    public function returnsArtistAlbumAndSongMatches(): void
    {
        $user = create_user();

        $artist = Artist::factory()->createOne(['name' => 'Radiohead', 'user_id' => $user->id]);
        $album = Album::factory()->createOne([
            'name' => 'In Rainbows',
            'artist_id' => $artist->id,
            'artist_name' => $artist->name,
            'user_id' => $user->id,
        ]);
        Song::factory()->createOne([
            'title' => 'Radiohead Karma',
            'album_id' => $album->id,
            'owner_id' => $user->id,
        ]);

        $response = $this
            ->getJson("/rest/search3.view?apiKey={$user->subsonic_api_key}&f=json&query=Radiohead")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $payload = $response->json('subsonic-response.searchResult3');

        self::assertContains('Radiohead', array_column($payload['artist'] ?? [], 'name'));
    }

    #[Test]
    public function missingQueryReturnsEmptyResult(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/search3.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertExactJson([
                'subsonic-response' => [
                    'status' => 'ok',
                    'version' => '1.16.1',
                    'type' => 'koel',
                    'serverVersion' => koel_version(),
                    'openSubsonic' => true,
                    'searchResult3' => ['artist' => [], 'album' => [], 'song' => []],
                ],
            ]);
    }

    #[Test]
    public function emptyQueryReturnsEmptyResult(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/search3.view?apiKey={$user->subsonic_api_key}&f=json&query=")
            ->assertOk()
            ->assertJsonPath('subsonic-response.searchResult3', [
                'artist' => [],
                'album' => [],
                'song' => [],
            ]);
    }

    #[Test]
    public function honorsArtistCountLimit(): void
    {
        $user = create_user();

        foreach (['Pearl Jam', 'Pearl Harbor', 'Pearl S Buck'] as $name) {
            $artist = Artist::factory()->createOne(['name' => $name, 'user_id' => $user->id]);
            Album::factory()->createOne([
                'artist_id' => $artist->id,
                'artist_name' => $artist->name,
                'user_id' => $user->id,
            ]);
        }

        $response = $this->getJson(
            "/rest/search3.view?apiKey={$user->subsonic_api_key}&f=json&query=Pearl&artistCount=1",
        )->assertOk();

        self::assertCount(1, $response->json('subsonic-response.searchResult3.artist') ?? []);
    }
}
