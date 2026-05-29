<?php

namespace Tests\Feature\Subsonic;

use App\Models\RadioStation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class UpdateInternetRadioStationTest extends TestCase
{
    #[Test]
    public function updatesAnOwnedStation(): void
    {
        $user = create_user();
        $station = RadioStation::factory()->createOne([
            'user_id' => $user->id,
            'name' => 'Old Name',
            'url' => 'https://old.example.com/stream',
            'homepage_url' => 'https://old.example.com',
        ]);

        $this
            ->getJson(sprintf(
                '/rest/updateInternetRadioStation.view?apiKey=%s&f=json&id=%s&name=%s&streamUrl=%s&homepageUrl=%s',
                $user->subsonic_api_key,
                $station->id,
                urlencode('New Name'),
                urlencode('https://new.example.com/stream'),
                urlencode('https://new.example.com'),
            ))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $station->refresh();
        self::assertSame('New Name', $station->name);
        self::assertSame('https://new.example.com/stream', $station->url);
        self::assertSame('https://new.example.com', $station->homepage_url);
    }

    #[Test]
    public function preservesHomepageUrlWhenOmitted(): void
    {
        $user = create_user();
        $station = RadioStation::factory()->createOne([
            'user_id' => $user->id,
            'homepage_url' => 'https://keep.example.com',
        ]);

        $this
            ->getJson(sprintf(
                '/rest/updateInternetRadioStation.view?apiKey=%s&f=json&id=%s&name=%s&streamUrl=%s',
                $user->subsonic_api_key,
                $station->id,
                urlencode('Updated Name'),
                urlencode('https://updated.example.com/stream'),
            ))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $station->refresh();
        self::assertSame('https://keep.example.com', $station->homepage_url);
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson(sprintf(
                '/rest/updateInternetRadioStation.view?apiKey=%s&f=json&id=%s&name=%s&streamUrl=%s',
                $user->subsonic_api_key,
                'does-not-exist',
                urlencode('Nope'),
                urlencode('https://nope.example.com'),
            ))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);
    }
}
