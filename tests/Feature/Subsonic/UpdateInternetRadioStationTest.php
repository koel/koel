<?php

namespace Tests\Feature\Subsonic;

use App\Models\RadioStation;
use Illuminate\Support\Arr;
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
            ->getJson(
                '/rest/updateInternetRadioStation.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                        'id' => $station->id,
                        'name' => 'New Name',
                        'streamUrl' => 'https://new.example.com/stream',
                        'homepageUrl' => 'https://new.example.com',
                    ]),
            )
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
            ->getJson(
                '/rest/updateInternetRadioStation.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                        'id' => $station->id,
                        'name' => 'Updated Name',
                        'streamUrl' => 'https://updated.example.com/stream',
                    ]),
            )
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
            ->getJson(
                '/rest/updateInternetRadioStation.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                        'id' => 'does-not-exist',
                        'name' => 'Nope',
                        'streamUrl' => 'https://nope.example.com',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);
    }
}
