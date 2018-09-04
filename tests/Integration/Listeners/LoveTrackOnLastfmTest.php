<?php

namespace Tests\Integration\Listeners;

use App\Events\SongLikeToggled;
use App\Listeners\LoveTrackOnLastfm;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Mockery;
use Mockery\MockInterface;
use Tests\Feature\TestCase;

class LoveTrackOnLastfmTest extends TestCase
{
    public function testHandle()
    {
        $user = factory(User::class)->create();
        $song = factory(Song::class)->create();

        $interaction = Interaction::create([
            'user_id' => $user->id,
            'song_id' => $song->id,
        ]);

        /** @var LastfmService|MockInterface $lastfm */
        $lastfm = Mockery::mock(LastfmService::class);
        $lastfm->shouldReceive('enabled')->andReturn(true);
        $lastfm->shouldReceive('getUserSessionKey')->andReturn('foo');
        $lastfm->shouldReceive('isUserConnected')->andReturn(true);

        $lastfm->shouldReceive('toggleLoveTrack')
            ->with($interaction->song->title, $interaction->song->album->artist->name, 'foo', false)
            ->once();

        (new LoveTrackOnLastfm($lastfm))->handle(new SongLikeToggled($interaction, $user));
    }
}
