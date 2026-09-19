<?php

namespace Tests\Feature;

use App\Models\Podcast;
use App\Models\RadioStation;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class DisableablePodcastsAndRadioTest extends TestCase
{
    private const int SUBSONIC_DATA_NOT_FOUND = 70;

    #[Test]
    public function servePodcastsWhenEnabled(): void
    {
        config(['koel.podcasts.enabled' => true]);

        $this->getAs('api/podcasts')->assertSuccessful();
    }

    #[Test]
    public function refusePodcastsWhenDisabled(): void
    {
        config(['koel.podcasts.enabled' => false]);

        $podcast = Podcast::factory()->createOne();

        $this->getAs('api/podcasts')->assertNotFound();
        $this->getAs("api/podcasts/{$podcast->id}")->assertNotFound();
        $this->getAs("api/podcasts/{$podcast->id}/episodes")->assertNotFound();
        $this->deleteAs("api/podcasts/{$podcast->id}/subscriptions")->assertNotFound();
    }

    #[Test]
    public function serveRadioWhenEnabled(): void
    {
        config(['koel.radio.enabled' => true]);

        $this->getAs('api/radio/stations')->assertSuccessful();
    }

    #[Test]
    public function refuseRadioWhenDisabled(): void
    {
        config(['koel.radio.enabled' => false]);

        $station = RadioStation::factory()->createOne();

        $this->getAs('api/radio/stations')->assertNotFound();
        $this->getAs("api/radio/stations/{$station->id}")->assertNotFound();
        $this->getAs("api/radio/stations/{$station->id}/now-playing")->assertNotFound();
    }

    #[Test]
    public function refuseSubsonicEndpointsWhenDisabled(): void
    {
        config(['koel.podcasts.enabled' => false, 'koel.radio.enabled' => false]);

        $query = Arr::query(['apiKey' => create_admin()->subsonic_api_key, 'f' => 'json']);

        foreach (['getPodcasts', 'getInternetRadioStations'] as $endpoint) {
            $this
                ->getJson("/rest/$endpoint.view?$query")
                ->assertOk()
                ->assertJsonPath('subsonic-response.status', 'failed')
                ->assertJsonPath('subsonic-response.error.code', self::SUBSONIC_DATA_NOT_FOUND);
        }
    }

    #[Test]
    public function announceTheSwitchesInTheInitialData(): void
    {
        config(['koel.podcasts.enabled' => false, 'koel.radio.enabled' => true]);

        $this->getAs('api/data')->assertJsonPath('uses_podcasts', false)->assertJsonPath('uses_radio', true);
    }
}
