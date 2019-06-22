<?php

namespace Tests\Integration\Listeners;

use App\Events\SongStartedPlaying;
use App\Listeners\UpdateLastfmNowPlaying;
use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Tests\Feature\TestCase;

class UpdateLastfmNowPlayingTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testUpdateNowPlayingStatus()
    {
        $this->withoutEvents();
        $this->createSampleMediaSet();

        $user = factory(User::class)->create(['preferences' => ['lastfm_session_key' => 'bar']]);
        $song = Song::first();

        /** @var LastfmService|MockInterface $lastfm */
        $lastfm = Mockery::mock(LastfmService::class, ['enabled' => true]);
        $lastfm->shouldReceive('updateNowPlaying')
            ->once()
            ->with($song->album->artist->name, $song->title, $song->album->name, $song->length, 'bar');

        (new UpdateLastfmNowPlaying($lastfm))->handle(new SongStartedPlaying($song, $user));
    }
}
