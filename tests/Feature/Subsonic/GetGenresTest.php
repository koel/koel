<?php

namespace Tests\Feature\Subsonic;

use App\Http\Responses\Subsonic\Resources\GenreResource;
use App\Models\Genre;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetGenresTest extends TestCase
{
    #[Test]
    public function returnsGenresWithCounts(): void
    {
        $user = create_user();

        $rock = Genre::factory()->createOne(['name' => 'Rock']);
        $song = Song::factory()->createOne(['owner_id' => $user->id]);
        $song->genres()->attach($rock->id);

        $response = $this
            ->getJson('/rest/getGenres.view?apiKey=' . $user->subsonic_api_key . '&f=json')
            ->assertOk()
            ->assertJsonStructure([
                'subsonic-response' => [
                    'genres' => ['genre' => ['*' => GenreResource::JSON_STRUCTURE]],
                ],
            ]);

        $genres = collect($response->json('subsonic-response.genres.genre'));
        $rockEntry = $genres->firstWhere('value', 'Rock');

        self::assertNotNull($rockEntry);
        self::assertSame(1, $rockEntry['songCount']);
    }
}
