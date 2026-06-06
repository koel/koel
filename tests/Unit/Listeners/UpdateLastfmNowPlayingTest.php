<?php

namespace Tests\Unit\Listeners;

use App\Events\PlaybackStarted;
use App\Listeners\UpdateLastfmNowPlaying;
use App\Models\Song;
use App\Services\Integrations\LastfmService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class UpdateLastfmNowPlayingTest extends TestCase
{
    #[Test]
    public function updateNowPlayingStatus(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne();
        $lastfm = Mockery::mock(LastfmService::class, ['enabled' => true]);

        $lastfm->expects('updateNowPlaying')->with($song, $user);

        (new UpdateLastfmNowPlaying($lastfm))->handle(new PlaybackStarted($song, $user));
    }
}
