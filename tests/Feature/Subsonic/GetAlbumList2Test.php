<?php

namespace Tests\Feature\Subsonic;

use App\Enums\FavoriteableType;
use App\Models\Album;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Interaction;
use App\Models\Song;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetAlbumList2Test extends TestCase
{
    #[Test]
    public function newestReturnsRecentAlbums(): void
    {
        $user = create_user();

        Album::factory()->createMany([
            ['name' => 'Older', 'user_id' => $user->id, 'created_at' => now()->subDays(10)],
            ['name' => 'Newer', 'user_id' => $user->id, 'created_at' => now()],
        ]);

        $response = $this
            ->getJson("/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}&f=json&type=newest&size=2")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $names = array_column($response->json('subsonic-response.albumList2.album'), 'name');
        self::assertSame(['Newer', 'Older'], $names);
    }

    #[Test]
    public function alphabeticalByNameRespectsOffsetAndSize(): void
    {
        $user = create_user();

        foreach (['Alpha', 'Bravo', 'Charlie', 'Delta'] as $name) {
            Album::factory()->createOne(['name' => $name, 'user_id' => $user->id]);
        }

        $response = $this->getJson(
            "/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}"
            . '&f=json&type=alphabeticalByName&size=2&offset=1',
        )->assertOk();

        $names = array_column($response->json('subsonic-response.albumList2.album'), 'name');
        self::assertSame(['Bravo', 'Charlie'], $names);
    }

    #[Test]
    public function randomReturnsRequestedNumber(): void
    {
        $user = create_user();

        Album::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->getJson(
            "/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}&f=json&type=random&size=3",
        )->assertOk();

        self::assertCount(3, $response->json('subsonic-response.albumList2.album'));
    }

    #[Test]
    public function starredReturnsOnlyFavoritedAlbums(): void
    {
        $user = create_user();

        $favorited = Album::factory()->createOne(['name' => 'Liked', 'user_id' => $user->id]);
        Album::factory()->createOne(['name' => 'NotLiked', 'user_id' => $user->id]);

        Favorite::factory()->createOne([
            'user_id' => $user->id,
            'favoriteable_type' => FavoriteableType::ALBUM->value,
            'favoriteable_id' => $favorited->id,
        ]);

        $response = $this->getJson(
            "/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}&f=json&type=starred",
        )->assertOk();

        $names = array_column($response->json('subsonic-response.albumList2.album') ?? [], 'name');
        self::assertSame(['Liked'], $names);
    }

    #[Test]
    public function frequentReturnsAtMostRequestedSize(): void
    {
        $user = create_user();

        Album::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this
            ->getJson("/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}&f=json&type=frequent&size=2")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        self::assertLessThanOrEqual(2, count($response->json('subsonic-response.albumList2.album') ?? []));
    }

    #[Test]
    public function recentReturnsMostRecentlyPlayedAlbums(): void
    {
        $user = create_user();

        $stale = Album::factory()->createOne(['name' => 'Stale', 'user_id' => $user->id]);
        $recent = Album::factory()->createOne(['name' => 'Recent', 'user_id' => $user->id]);
        $unplayed = Album::factory()->createOne(['name' => 'Unplayed', 'user_id' => $user->id]);

        $staleSong = Song::factory()->createOne(['album_id' => $stale->id, 'owner_id' => $user->id]);
        $recentSong = Song::factory()->createOne(['album_id' => $recent->id, 'owner_id' => $user->id]);
        Song::factory()->createOne(['album_id' => $unplayed->id, 'owner_id' => $user->id]);

        Interaction::factory()->createOne([
            'user_id' => $user->id,
            'song_id' => $staleSong->id,
            'play_count' => 5,
            'last_played_at' => now()->subDays(7),
        ]);
        Interaction::factory()->createOne([
            'user_id' => $user->id,
            'song_id' => $recentSong->id,
            'play_count' => 1,
            'last_played_at' => now()->subMinutes(5),
        ]);

        $response = $this
            ->getJson("/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}&f=json&type=recent&size=10")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $names = array_column($response->json('subsonic-response.albumList2.album') ?? [], 'name');
        self::assertSame(['Recent', 'Stale'], $names);
    }

    #[Test]
    public function byYearAscendingReturnsAlbumsInRange(): void
    {
        $user = create_user();

        Album::factory()->createMany([
            ['name' => 'Old', 'year' => 1965, 'user_id' => $user->id],
            ['name' => 'Mid', 'year' => 1980, 'user_id' => $user->id],
            ['name' => 'New', 'year' => 2020, 'user_id' => $user->id],
        ]);

        $response = $this->getJson(
            "/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}"
            . '&f=json&type=byYear&fromYear=1960&toYear=1990&size=10',
        )->assertOk();

        $names = array_column($response->json('subsonic-response.albumList2.album') ?? [], 'name');
        self::assertSame(['Old', 'Mid'], $names);
    }

    #[Test]
    public function byYearDescendingReversesSortWhenFromGreaterThanTo(): void
    {
        $user = create_user();

        Album::factory()->createMany([
            ['name' => 'Old', 'year' => 1965, 'user_id' => $user->id],
            ['name' => 'Mid', 'year' => 1980, 'user_id' => $user->id],
            ['name' => 'New', 'year' => 2020, 'user_id' => $user->id],
        ]);

        $response = $this->getJson(
            "/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}"
            . '&f=json&type=byYear&fromYear=2026&toYear=0&size=10',
        )->assertOk();

        $names = array_column($response->json('subsonic-response.albumList2.album') ?? [], 'name');
        self::assertSame(['New', 'Mid', 'Old'], $names);
    }

    #[Test]
    public function byYearWithoutFromYearReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}&f=json&type=byYear&toYear=2000")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function alphabeticalByArtistSortsByArtistThenAlbumName(): void
    {
        $user = create_user();

        Album::factory()->createMany([
            ['name' => 'Z Album', 'artist_name' => 'Alice', 'user_id' => $user->id],
            ['name' => 'A Album', 'artist_name' => 'Alice', 'user_id' => $user->id],
            ['name' => 'A Album', 'artist_name' => 'Bob', 'user_id' => $user->id],
        ]);

        $response = $this->getJson(
            '/rest/getAlbumList2.view?'
                . Arr::query([
                    'apiKey' => $user->subsonic_api_key,
                    'f' => 'json',
                    'type' => 'alphabeticalByArtist',
                    'size' => 10,
                ]),
        )->assertOk();

        $albums = $response->json('subsonic-response.albumList2.album');
        $tuples = array_map(static fn (array $a) => [$a['artist'], $a['name']], $albums);

        self::assertSame([['Alice', 'A Album'], ['Alice', 'Z Album'], ['Bob', 'A Album']], $tuples);
    }

    #[Test]
    public function byGenreReturnsAlbumsWithSongsInThatGenre(): void
    {
        $user = create_user();

        $rock = Genre::factory()->createOne(['name' => 'Rock']);
        $jazz = Genre::factory()->createOne(['name' => 'Jazz']);

        $matching = Album::factory()->createOne(['name' => 'Rock Album', 'user_id' => $user->id]);
        $other = Album::factory()->createOne(['name' => 'Jazz Album', 'user_id' => $user->id]);

        $rockSong = Song::factory()->createOne(['album_id' => $matching->id, 'owner_id' => $user->id]);
        $jazzSong = Song::factory()->createOne(['album_id' => $other->id, 'owner_id' => $user->id]);

        $rockSong->genres()->attach($rock->id);
        $jazzSong->genres()->attach($jazz->id);

        $response = $this->getJson(
            '/rest/getAlbumList2.view?'
                . Arr::query([
                    'apiKey' => $user->subsonic_api_key,
                    'f' => 'json',
                    'type' => 'byGenre',
                    'genre' => 'Rock',
                    'size' => 10,
                ]),
        )->assertOk();

        $names = array_column($response->json('subsonic-response.albumList2.album') ?? [], 'name');
        self::assertSame(['Rock Album'], $names);
    }

    #[Test]
    public function byGenreWithoutGenreReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson(
                '/rest/getAlbumList2.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                        'type' => 'byGenre',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function unsupportedTypeReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}&f=json&type=bogus")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function missingTypeReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 10);
    }
}
