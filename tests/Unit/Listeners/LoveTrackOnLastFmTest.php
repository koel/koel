<?php

namespace Tests\Unit\Listeners;

use App\Events\SongLikeToggled;
use App\Listeners\LoveTrackOnLastfm;
use App\Models\Interaction;
use App\Services\LastfmService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoveTrackOnLastFmTest extends TestCase
{
    #[Test]
    public function handle(): void
    {
        /** @var Interaction $interaction */
        $interaction = Interaction::factory()->create();

        $lastfm = Mockery::mock(LastfmService::class, ['enabled' => true]);

        $lastfm->expects('toggleLoveTrack')->with($interaction->song, $interaction->user, $interaction->liked);

        (new LoveTrackOnLastfm($lastfm))->handle(new SongLikeToggled($interaction));
    }
}
