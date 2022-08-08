<?php

namespace Tests\Unit\Listeners;

use App\Events\SongLikeToggled;
use App\Listeners\LoveTrackOnLastfm;
use App\Models\Interaction;
use App\Services\LastfmService;
use Mockery;
use Tests\Feature\TestCase;

class LoveTrackOnLastFmTest extends TestCase
{
    public function testHandle(): void
    {
        /** @var Interaction $interaction */
        $interaction = Interaction::factory()->create();

        $lastfm = Mockery::mock(LastfmService::class, ['enabled' => true]);

        $lastfm->shouldReceive('toggleLoveTrack')
            ->with($interaction->song, $interaction->user, $interaction->liked);

        (new LoveTrackOnLastfm($lastfm))->handle(new SongLikeToggled($interaction));
    }
}
