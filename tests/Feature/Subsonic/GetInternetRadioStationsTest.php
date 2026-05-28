<?php

namespace Tests\Feature\Subsonic;

use App\Models\RadioStation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetInternetRadioStationsTest extends TestCase
{
    #[Test]
    public function returnsTheUsersRadioStations(): void
    {
        $user = create_user();

        RadioStation::factory()->createMany([
            ['name' => 'BBC 6', 'url' => 'https://stream.bbc/6', 'user_id' => $user->id],
            ['name' => 'KEXP', 'url' => 'https://kexp.org/stream', 'user_id' => $user->id],
        ]);

        $response = $this
            ->getJson("/rest/getInternetRadioStations.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $stations = $response->json('subsonic-response.internetRadioStations.internetRadioStation') ?? [];

        self::assertCount(2, $stations);

        $names = array_column($stations, 'name');
        self::assertContains('BBC 6', $names);
        self::assertContains('KEXP', $names);

        foreach ($stations as $station) {
            self::assertNotEmpty($station['streamUrl']);
        }
    }

    #[Test]
    public function emptyWhenUserHasNoRadioStations(): void
    {
        $user = create_user();

        $response = $this->getJson(
            "/rest/getInternetRadioStations.view?apiKey={$user->subsonic_api_key}&f=json",
        )->assertOk();

        self::assertSame([], $response->json('subsonic-response.internetRadioStations.internetRadioStation') ?? []);
    }
}
