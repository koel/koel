<?php

namespace Tests\Unit\Listeners;

use App\Events\SongLikeToggled;
use App\Listeners\LoveTrackOnLastfm;
use App\Models\Interaction;
use App\Services\LastfmService;
use App\Values\LastfmLoveTrackParameters;
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
            ->with(
                Mockery::on(static function (LastfmLoveTrackParameters $params) use ($interaction): bool {
                    self::assertSame($interaction->song->title, $params->trackName);
                    self::assertSame($interaction->song->artist->name, $params->artistName);

                    return true;
                }),
                $interaction->user->lastfm_session_key,
                $interaction->liked
            );

        (new LoveTrackOnLastfm($lastfm))->handle(new SongLikeToggled($interaction));
    }
}
