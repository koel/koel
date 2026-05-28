<?php

namespace Tests\Feature\Subsonic;

use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Models\Genre;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetSongsByGenreTest extends TestCase
{
    #[Test]
    public function returnsSongsFromTheNamedGenre(): void
    {
        $user = create_user();
        $rock = Genre::factory()->createOne(['name' => 'Rock']);
        $songs = Song::factory()->count(3)->create(['owner_id' => $user->id]);

        foreach ($songs as $song) {
            $song->genres()->attach($rock->id);
        }

        $response = $this
            ->getJson("/rest/getSongsByGenre.view?apiKey={$user->subsonic_api_key}&f=json&genre=Rock")
            ->assertOk()
            ->assertJsonStructure([
                'subsonic-response' => [
                    'songsByGenre' => ['song' => ['*' => SongResource::JSON_STRUCTURE]],
                ],
            ]);

        self::assertCount(3, $response->json('subsonic-response.songsByGenre.song'));
    }

    #[Test]
    public function unknownGenreReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getSongsByGenre.view?apiKey={$user->subsonic_api_key}&f=json&genre=DoesNotExist")
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 70);
    }

    #[Test]
    public function missingGenreReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getSongsByGenre.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }
}
