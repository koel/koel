<?php

namespace Tests\Feature\Subsonic;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class Search2Test extends TestCase
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
    public function returnsAlbumsAsChildElementsUnderSearchResult2Wrapper(): void
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
            ->getJson(
                '/rest/search2.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                        'query' => 'Radiohead',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $payload = $response->json('subsonic-response.searchResult2');

        self::assertContains('Radiohead', array_column($payload['artist'] ?? [], 'name'));
        self::assertContains('In Rainbows', array_column($payload['album'] ?? [], 'title'));
        self::assertContains('Radiohead Karma', array_column($payload['song'] ?? [], 'title'));

        foreach ($payload['album'] as $album) {
            self::assertTrue($album['isDir']);
        }
    }

    #[Test]
    public function emptyQueryReturnsEmptyResult(): void
    {
        $user = create_user();

        $response = $this->getJson(
            '/rest/search2.view?'
                . Arr::query([
                    'apiKey' => $user->subsonic_api_key,
                    'f' => 'json',
                    'query' => '',
                ]),
        )->assertOk();

        self::assertSame(
            [
                'artist' => [],
                'album' => [],
                'song' => [],
            ],
            $response->json('subsonic-response.searchResult2'),
        );
    }
}
