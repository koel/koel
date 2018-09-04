<?php

namespace Tests\Integration\Listeners;

use App\Events\SongLikeToggled;
use App\Listeners\LoveTrackOnLastfm;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Tests\Feature\TestCase;

class LoveTrackOnLastfmTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testHandle()
    {
        $this->withoutEvents();
        $this->createSampleMediaSet();

        $user = factory(User::class)->create(['preferences' => ['lastfm_session_key' => 'bar']]);

        $interaction = Interaction::create([
            'user_id' => $user->id,
            'song_id' => Song::first()->id,
        ]);

        /** @var LastfmService|MockInterface $lastfm */
        $lastfm = Mockery::mock(LastfmService::class, ['enabled' => true]);
        $lastfm->shouldReceive('toggleLoveTrack')
            ->once()
            ->with($interaction->song->title, $interaction->song->album->artist->name, 'bar', false);

        (new LoveTrackOnLastfm($lastfm))->handle(new SongLikeToggled($interaction, $user));
    }
}
