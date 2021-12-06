<?php

namespace Tests\Unit\Listeners;

use App\Events\SongLikeToggled;
use App\Listeners\LoveTrackOnLastfm;
use App\Models\Interaction;
use App\Models\User;
use App\Services\LastfmService;
use App\Values\LastfmLoveTrackParameters;
use Mockery;
use Tests\Feature\TestCase;

class LoveTrackOnLastFmTest extends TestCase
{
    public function testHandle(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Interaction $interaction */
        $interaction = Interaction::factory()->create();

        $lastfm = Mockery::mock(LastfmService::class, ['enabled' => true]);
        $lastfm->shouldReceive('toggleLoveTrack')
            ->with(
                Mockery::on(static function (LastfmLoveTrackParameters $params) use ($interaction): bool {
                    self::assertSame($interaction->song->title, $params->getTrackName());
                    self::assertSame($interaction->song->artist->name, $params->getArtistName());

                    return true;
                }),
                $user->lastfm_session_key,
                $interaction->liked
            );

        (new LoveTrackOnLastfm($lastfm))->handle(new SongLikeToggled($interaction, $user));
    }
}
