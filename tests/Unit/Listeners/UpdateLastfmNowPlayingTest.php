<?php

namespace Tests\Unit\Listeners;

use App\Events\PlaybackStarted;
use App\Listeners\UpdateLastfmNowPlaying;
use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Mockery;
use Tests\TestCase;

class UpdateLastfmNowPlayingTest extends TestCase
{
    public function testUpdateNowPlayingStatus(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Song $song */
        $song = Song::factory()->create();

        $lastfm = Mockery::mock(LastfmService::class, ['enabled' => true]);

        $lastfm->shouldReceive('updateNowPlaying')
            ->with($song, $user)
            ->once();

        (new UpdateLastfmNowPlaying($lastfm))->handle(new PlaybackStarted($song, $user));
    }
}
