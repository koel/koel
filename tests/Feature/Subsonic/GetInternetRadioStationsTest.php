<?php

namespace Tests\Feature\Subsonic;

use App\Http\Responses\Subsonic\Resources\RadioStationResource;
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
            [
                'name' => 'BBC 6',
                'url' => 'https://stream.bbc/6',
                'homepage_url' => 'https://www.bbc.co.uk/sounds/play/live:bbc_6music',
                'user_id' => $user->id,
            ],
            ['name' => 'KEXP', 'url' => 'https://kexp.org/stream', 'user_id' => $user->id],
        ]);

        $response = $this
            ->getJson("/rest/getInternetRadioStations.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertJsonStructure([
                'subsonic-response' => [
                    'internetRadioStations' => [
                        'internetRadioStation' => ['*' => RadioStationResource::JSON_STRUCTURE],
                    ],
                ],
            ]);

        $stations = $response->json('subsonic-response.internetRadioStations.internetRadioStation') ?? [];

        self::assertCount(2, $stations);

        $byName = collect($stations)->keyBy('name');

        self::assertContains('BBC 6', $byName->keys());
        self::assertContains('KEXP', $byName->keys());

        self::assertSame('https://www.bbc.co.uk/sounds/play/live:bbc_6music', $byName['BBC 6']['homepageUrl']);
        self::assertArrayNotHasKey('homepageUrl', $byName['KEXP']);

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
