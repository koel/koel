<?php

namespace Tests\Integration\Listeners;

use App\Events\SongStartedPlaying;
use App\Listeners\UpdateLastfmNowPlaying;
use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Mockery;
use Mockery\MockInterface;
use Tests\Feature\TestCase;

class UpdateLastfmNowPlayingTest extends TestCase
{
    public function testUpdateNowPlayingStatus()
    {
        $user = factory(User::class)->make();
        $song = factory(Song::class)->make();

        /** @var LastfmService|MockInterface $lastfm */
        $lastfm = Mockery::mock(LastfmService::class);
        $lastfm->shouldReceive('enabled')->andReturn(true);
        $lastfm->shouldReceive('getUserSessionKey')->andReturn('foo');
        $lastfm->shouldReceive('isUserConnected')->andReturn(true);

        $lastfm->shouldReceive('updateNowPlaying')
            ->once()
            ->with($song->album->artist->name, $song->title, $song->album->name, $song->length, 'foo');

        (new UpdateLastfmNowPlaying($lastfm))->handle(new SongStartedPlaying($song, $user));
    }
}
