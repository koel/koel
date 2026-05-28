<?php

namespace Tests\Feature\Subsonic;

use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetRandomSongsTest extends TestCase
{
    #[Test]
    public function returnsRequestedNumber(): void
    {
        $user = create_user();
        Song::factory()->count(5)->create(['owner_id' => $user->id]);

        $response = $this
            ->getJson("/rest/getRandomSongs.view?apiKey={$user->subsonic_api_key}&f=json&size=3")
            ->assertOk()
            ->assertJsonStructure([
                'subsonic-response' => [
                    'randomSongs' => ['song' => ['*' => SongResource::JSON_STRUCTURE]],
                ],
            ]);

        self::assertCount(3, $response->json('subsonic-response.randomSongs.song'));
    }

    #[Test]
    public function defaultsSizeWhenOmitted(): void
    {
        $user = create_user();
        Song::factory()->count(15)->create(['owner_id' => $user->id]);

        $response = $this->getJson("/rest/getRandomSongs.view?apiKey={$user->subsonic_api_key}&f=json")->assertOk();

        self::assertCount(10, $response->json('subsonic-response.randomSongs.song'));
    }

    #[Test]
    public function rejectsSizeOver500(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getRandomSongs.view?apiKey={$user->subsonic_api_key}&f=json&size=9001")
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function rejectsSizeBelowOne(): void
    {
        $user = create_user();

        foreach (['0', '-1'] as $bad) {
            $this
                ->getJson("/rest/getRandomSongs.view?apiKey={$user->subsonic_api_key}&f=json&size={$bad}")
                ->assertOk()
                ->assertJsonPath('subsonic-response.error.code', 10);
        }
    }
}
