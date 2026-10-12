<?php

namespace Tests\Unit\Listeners;

use App\Events\PlaybackStarted;
use App\Listeners\UpdateNowPlaying;
use App\Models\Artist;
use App\Models\Song;
use App\Services\ScrobbleService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class UpdateNowPlayingTest extends TestCase
{
    #[Test]
    public function updateNowPlayingStatus(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne();
        $scrobbleService = Mockery::mock(ScrobbleService::class);

        $scrobbleService->expects('updateNowPlaying')->with($song, $user);

        (new UpdateNowPlaying($scrobbleService))->handle(new PlaybackStarted($song, $user));
    }

    #[Test]
    public function doesNotUpdateNowPlayingStatusForUnknownArtist(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne(['name' => Artist::UNKNOWN_NAME]);
        $song = Song::factory()->for($artist)->createOne();
        $scrobbleService = Mockery::mock(ScrobbleService::class);

        $scrobbleService->shouldNotReceive('updateNowPlaying');

        (new UpdateNowPlaying($scrobbleService))->handle(new PlaybackStarted($song, $user));
    }
}
