<?php

namespace Tests\Integration\Listeners;

use App\Events\SongStartedPlaying;
use App\Listeners\UpdateLastfmNowPlaying;
use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Mockery;
use Tests\Feature\TestCase;

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
            ->with($song->artist->name, $song->title, $song->album->name, $song->length, $user->lastfm_session_key);

        (new UpdateLastfmNowPlaying($lastfm))->handle(new SongStartedPlaying($song, $user));
    }
}
