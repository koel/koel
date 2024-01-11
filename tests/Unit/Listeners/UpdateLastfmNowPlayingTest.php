<?php

namespace Tests\Unit\Listeners;

use App\Events\PlaybackStarted;
use App\Listeners\UpdateLastfmNowPlaying;
use App\Models\Song;
use App\Services\LastfmService;
use Mockery;
use Tests\TestCase;

use function Tests\create_user;

class UpdateLastfmNowPlayingTest extends TestCase
{
    public function testUpdateNowPlayingStatus(): void
    {
        $user = create_user();

        /** @var Song $song */
        $song = Song::factory()->create();
        $lastfm = Mockery::mock(LastfmService::class, ['enabled' => true]);

        $lastfm->shouldReceive('updateNowPlaying')
            ->with($song, $user)
            ->once();

        (new UpdateLastfmNowPlaying($lastfm))->handle(new PlaybackStarted($song, $user));
    }
}
