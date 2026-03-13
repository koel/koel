<?php

namespace Tests\Feature;

use App\Models\RadioStation;
use App\Services\RadioStreamMetadata;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class RadioStationNowPlayingTest extends TestCase
{
    #[Test]
    public function getNowPlaying(): void
    {
        $user = create_user();
        $station = RadioStation::factory()->createOne(['user_id' => $user->id]);

        RadioStreamMetadata::cache($station, 'Artist - Great Song');

        $this
            ->getAs("/api/radio/stations/{$station->id}/now-playing", $user)
            ->assertOk()
            ->assertJsonFragment([
                'stream_title' => 'Artist - Great Song',
            ]);
    }

    #[Test]
    public function getNowPlayingWhenNoCachedData(): void
    {
        $user = create_user();
        $station = RadioStation::factory()->createOne(['user_id' => $user->id]);

        $this
            ->getAs("/api/radio/stations/{$station->id}/now-playing", $user)
            ->assertOk()
            ->assertJsonFragment([
                'stream_title' => null,
                'updated_at' => null,
            ]);
    }

    #[Test]
    public function cannotGetNowPlayingForInaccessibleStation(): void
    {
        $user = create_user();
        $station = RadioStation::factory()->createOne(['is_public' => false]);

        $this->getAs("/api/radio/stations/{$station->id}/now-playing", $user)->assertForbidden();
    }
}
