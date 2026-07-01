<?php

namespace Tests\Feature\Subsonic;

use App\Enums\FavoriteableType;
use App\Http\Responses\Subsonic\Resources\AlbumResource;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Models\Album;
use App\Models\Favorite;
use App\Models\Interaction;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetAlbumTest extends TestCase
{
    #[Test]
    public function returnsAlbumWithSongs(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne(['name' => 'OK Computer', 'user_id' => $user->id]);

        Song::factory()->createMany([
            ['title' => 'Airbag', 'album_id' => $album->id, 'owner_id' => $user->id],
            ['title' => 'Paranoid Android', 'album_id' => $album->id, 'owner_id' => $user->id],
        ]);

        $response = $this
            ->getJson("/rest/getAlbum.view?apiKey={$user->subsonic_api_key}&f=json&id={$album->id}")
            ->assertOk()
            ->assertJsonStructure([
                'subsonic-response' => [
                    'album' => array_merge(AlbumResource::JSON_STRUCTURE, [
                        'song' => ['*' => SongResource::JSON_STRUCTURE],
                    ]),
                ],
            ])
            ->assertJsonPath('subsonic-response.album.id', $album->id)
            ->assertJsonPath('subsonic-response.album.name', 'OK Computer');

        $titles = collect($response->json('subsonic-response.album.song'))->pluck('title')->all();
        self::assertContains('Airbag', $titles);
        self::assertContains('Paranoid Android', $titles);
    }

    #[Test]
    public function populatesStarredDateWhenFavorited(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne(['user_id' => $user->id]);

        $favoritedAt = now()->subDays(2)->startOfSecond();

        Favorite::factory()->createOne([
            'user_id' => $user->id,
            'favoriteable_type' => FavoriteableType::ALBUM->value,
            'favoriteable_id' => $album->id,
            'created_at' => $favoritedAt,
        ]);

        $this
            ->getJson("/rest/getAlbum.view?apiKey={$user->subsonic_api_key}&f=json&id={$album->id}")
            ->assertOk()
            ->assertJsonPath('subsonic-response.album.starred', $favoritedAt->toIso8601String());
    }

    #[Test]
    public function omitsStarredDateWhenNotFavorited(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne(['user_id' => $user->id]);

        $response = $this->getJson(
            "/rest/getAlbum.view?apiKey={$user->subsonic_api_key}&f=json&id={$album->id}",
        )->assertOk();

        self::assertArrayNotHasKey('starred', $response->json('subsonic-response.album'));
    }

    #[Test]
    public function populatesPlayedDateFromMostRecentlyPlayedSong(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne(['user_id' => $user->id]);

        $stale = Song::factory()->createOne(['album_id' => $album->id, 'owner_id' => $user->id]);
        $recent = Song::factory()->createOne(['album_id' => $album->id, 'owner_id' => $user->id]);

        Interaction::factory()->createOne([
            'user_id' => $user->id,
            'song_id' => $stale->id,
            'last_played_at' => now()->subDays(9),
        ]);

        $lastPlayedAt = now()->subMinutes(2)->startOfSecond();

        Interaction::factory()->createOne([
            'user_id' => $user->id,
            'song_id' => $recent->id,
            'last_played_at' => $lastPlayedAt,
        ]);

        $this
            ->getJson("/rest/getAlbum.view?apiKey={$user->subsonic_api_key}&f=json&id={$album->id}")
            ->assertOk()
            ->assertJsonPath('subsonic-response.album.played', $lastPlayedAt->toIso8601String());
    }

    #[Test]
    public function omitsPlayedDateWhenNeverPlayed(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne(['user_id' => $user->id]);
        Song::factory()->createOne(['album_id' => $album->id, 'owner_id' => $user->id]);

        $response = $this->getJson(
            "/rest/getAlbum.view?apiKey={$user->subsonic_api_key}&f=json&id={$album->id}",
        )->assertOk();

        self::assertArrayNotHasKey('played', $response->json('subsonic-response.album'));
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getAlbum.view?apiKey={$user->subsonic_api_key}&f=json&id=does-not-exist")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);
    }
}
