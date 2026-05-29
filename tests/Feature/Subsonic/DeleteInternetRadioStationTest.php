<?php

namespace Tests\Feature\Subsonic;

use App\Models\RadioStation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class DeleteInternetRadioStationTest extends TestCase
{
    #[Test]
    public function deletesAnOwnedStation(): void
    {
        $user = create_user();
        $station = RadioStation::factory()->createOne(['user_id' => $user->id]);

        $this
            ->getJson(sprintf(
                '/rest/deleteInternetRadioStation.view?apiKey=%s&f=json&id=%s',
                $user->subsonic_api_key,
                $station->id,
            ))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        self::assertNull(RadioStation::query()->find($station->id));
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson(sprintf(
                '/rest/deleteInternetRadioStation.view?apiKey=%s&f=json&id=%s',
                $user->subsonic_api_key,
                'does-not-exist',
            ))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);
    }
}
