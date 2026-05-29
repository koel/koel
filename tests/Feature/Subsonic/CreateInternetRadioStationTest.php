<?php

namespace Tests\Feature\Subsonic;

use App\Models\RadioStation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class CreateInternetRadioStationTest extends TestCase
{
    #[Test]
    public function createsAStation(): void
    {
        $user = create_user();

        $this
            ->getJson(sprintf(
                '/rest/createInternetRadioStation.view?apiKey=%s&f=json&name=%s&streamUrl=%s&homepageUrl=%s',
                $user->subsonic_api_key,
                urlencode('NTS Live'),
                urlencode('https://stream-relay-geo.ntslive.net/stream'),
                urlencode('https://www.nts.live'),
            ))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $station = RadioStation::query()->where('user_id', $user->id)->where('name', 'NTS Live')->firstOrFail();

        self::assertSame('https://stream-relay-geo.ntslive.net/stream', $station->url);
        self::assertSame('https://www.nts.live', $station->homepage_url);
    }

    #[Test]
    public function homepageUrlIsOptional(): void
    {
        $user = create_user();

        $this
            ->getJson(sprintf(
                '/rest/createInternetRadioStation.view?apiKey=%s&f=json&name=%s&streamUrl=%s',
                $user->subsonic_api_key,
                urlencode('Plain Station'),
                urlencode('https://example.com/stream'),
            ))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $station = RadioStation::query()->where('user_id', $user->id)->where('name', 'Plain Station')->firstOrFail();

        self::assertNull($station->homepage_url);
    }

    #[Test]
    public function missingStreamUrlReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson(sprintf(
                '/rest/createInternetRadioStation.view?apiKey=%s&f=json&name=%s',
                $user->subsonic_api_key,
                urlencode('Missing'),
            ))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 10);
    }
}
