<?php

namespace Tests\Feature\Subsonic;

use App\Enums\FavoriteableType;
use App\Http\Responses\Subsonic\Resources\AlbumResource;
use App\Http\Responses\Subsonic\Resources\ArtistResource;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Favorite;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetStarred2Test extends TestCase
{
    #[Test]
    public function returnsFavoritedEntitiesAcrossTypes(): void
    {
        $user = create_user();

        $song = Song::factory()->createOne(['owner_id' => $user->id]);
        $album = Album::factory()->createOne(['user_id' => $user->id]);
        $artist = Artist::factory()->createOne(['user_id' => $user->id]);
        Album::factory()
            ->count(2)
            ->create([
                'artist_id' => $artist->id,
                'artist_name' => $artist->name,
                'user_id' => $user->id,
            ]);

        Favorite::factory()->createMany([
            [
                'user_id' => $user->id,
                'favoriteable_type' => FavoriteableType::PLAYABLE->value,
                'favoriteable_id' => $song->id,
            ],
            [
                'user_id' => $user->id,
                'favoriteable_type' => FavoriteableType::ALBUM->value,
                'favoriteable_id' => $album->id,
            ],
            [
                'user_id' => $user->id,
                'favoriteable_type' => FavoriteableType::ARTIST->value,
                'favoriteable_id' => $artist->id,
            ],
        ]);

        $response = $this
            ->getJson("/rest/getStarred2.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonStructure([
                'subsonic-response' => [
                    'starred2' => [
                        'song' => ['*' => SongResource::JSON_STRUCTURE],
                        'album' => ['*' => AlbumResource::JSON_STRUCTURE],
                        'artist' => ['*' => ArtistResource::JSON_STRUCTURE],
                    ],
                ],
            ]);

        $payload = $response->json('subsonic-response.starred2');
        self::assertContains($song->id, array_column($payload['song'], 'id'));
        self::assertContains($album->id, array_column($payload['album'], 'id'));
        self::assertContains($artist->id, array_column($payload['artist'], 'id'));

        $byId = collect($payload['artist'])->keyBy('id');
        self::assertSame(2, $byId[$artist->id]['albumCount']);
    }

    #[Test]
    public function emptyWhenNothingStarred(): void
    {
        $user = create_user();

        $response = $this->getJson("/rest/getStarred2.view?apiKey={$user->subsonic_api_key}&f=json")->assertOk();

        $payload = $response->json('subsonic-response.starred2');
        self::assertSame([], $payload['song'] ?? []);
        self::assertSame([], $payload['album'] ?? []);
        self::assertSame([], $payload['artist'] ?? []);
    }
}
