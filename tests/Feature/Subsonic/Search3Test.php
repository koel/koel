<?php

namespace Tests\Feature\Subsonic;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
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
    #[TestWith(['query='])] // empty query
    #[TestWith(['query=%22%22'])] // "" — what Symfonium/DSub send to enumerate the library
    #[TestWith(['query=%27%27'])] // ''
    #[TestWith(['query=%20%20'])] // whitespace only
    #[TestWith([''])] // no query param at all
    public function blankQueryBrowsesEntireLibrary(string $queryString): void
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
            'title' => 'Weird Fishes',
            'album_id' => $album->id,
            'owner_id' => $user->id,
        ]);

        $result = $this
            ->getJson("/rest/search3.view?apiKey={$user->subsonic_api_key}&f=json&$queryString")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->json('subsonic-response.searchResult3');

        self::assertContains('Radiohead', array_column($result['artist'] ?? [], 'name'));
        self::assertContains('In Rainbows', array_column($result['album'] ?? [], 'name'));
        self::assertContains('Weird Fishes', array_column($result['song'] ?? [], 'title'));
    }

    #[Test]
    public function browsingHonorsOffsetAndCount(): void
    {
        $user = create_user();

        $songIds = [];

        foreach (['A', 'B', 'C'] as $letter) {
            $artist = Artist::factory()->createOne(['name' => "Artist $letter", 'user_id' => $user->id]);
            $album = Album::factory()->createOne([
                'name' => "Album $letter",
                'artist_id' => $artist->id,
                'artist_name' => $artist->name,
                'user_id' => $user->id,
            ]);
            $songIds[] = Song::factory()->createOne(['album_id' => $album->id, 'owner_id' => $user->id])->id;
        }

        sort($songIds);

        $result = $this
            ->getJson(
                "/rest/search3.view?apiKey={$user->subsonic_api_key}&f=json&query="
                . '&artistCount=1&artistOffset=1&albumCount=1&albumOffset=1&songCount=1&songOffset=1',
            )
            ->assertOk()
            ->json('subsonic-response.searchResult3');

        self::assertSame(['Artist B'], array_column($result['artist'], 'name'));
        self::assertSame(['Album B'], array_column($result['album'], 'name'));
        self::assertSame([$songIds[1]], array_column($result['song'], 'id'));
    }

    #[Test]
    public function searchHonorsOffset(): void
    {
        $user = create_user();

        foreach (['A', 'B', 'C'] as $letter) {
            $artist = Artist::factory()->createOne(['name' => "Pearl Artist $letter", 'user_id' => $user->id]);
            $album = Album::factory()->createOne([
                'name' => "Pearl Album $letter",
                'artist_id' => $artist->id,
                'artist_name' => $artist->name,
                'user_id' => $user->id,
            ]);
            Song::factory()->createOne([
                'title' => "Pearl Song $letter",
                'album_id' => $album->id,
                'owner_id' => $user->id,
            ]);
        }

        $pages = [];

        foreach ([0, 1, 2] as $offset) {
            $pages[] = $this
                ->getJson(
                    "/rest/search3.view?apiKey={$user->subsonic_api_key}&f=json&query=Pearl"
                    . "&artistCount=1&artistOffset=$offset&albumCount=1&albumOffset=$offset"
                    . "&songCount=1&songOffset=$offset",
                )
                ->assertOk()
                ->json('subsonic-response.searchResult3');
        }

        $artistNames = array_merge(...array_map(static fn (array $page) => array_column(
            $page['artist'],
            'name',
        ), $pages));
        $albumNames = array_merge(...array_map(static fn (array $page) => array_column(
            $page['album'],
            'name',
        ), $pages));
        $songTitles = array_merge(...array_map(static fn (array $page) => array_column(
            $page['song'],
            'title',
        ), $pages));

        self::assertEqualsCanonicalizing(['Pearl Artist A', 'Pearl Artist B', 'Pearl Artist C'], $artistNames);
        self::assertEqualsCanonicalizing(['Pearl Album A', 'Pearl Album B', 'Pearl Album C'], $albumNames);
        self::assertEqualsCanonicalizing(['Pearl Song A', 'Pearl Song B', 'Pearl Song C'], $songTitles);
    }

    #[Test]
    #[TestWith(['artistOffset'])]
    #[TestWith(['albumOffset'])]
    #[TestWith(['songOffset'])]
    public function rejectsNegativeOffset(string $offsetParam): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/search3.view?apiKey={$user->subsonic_api_key}&f=json&query=&$offsetParam=-1")
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 10);
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
