<?php

namespace Tests\Feature\Subsonic;

use App\Models\RadioStation;
use Illuminate\Support\Arr;
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
            ->getJson(
                '/rest/deleteInternetRadioStation.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                        'id' => $station->id,
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        self::assertNull(RadioStation::query()->find($station->id));
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson(
                '/rest/deleteInternetRadioStation.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                        'id' => 'does-not-exist',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);
    }
}
