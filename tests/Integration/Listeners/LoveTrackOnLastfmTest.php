<?php

namespace Tests\Integration\Listeners;

use App\Events\SongLikeToggled;
use App\Jobs\LoveTrackOnLastfmJob;
use App\Listeners\LoveTrackOnLastfm;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Exception;
use Illuminate\Support\Facades\Queue;
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
        static::createSampleMediaSet();

        $user = factory(User::class)->create(['preferences' => ['lastfm_session_key' => 'bar']]);

        $interaction = Interaction::create([
            'user_id' => $user->id,
            'song_id' => Song::first()->id,
        ]);

        $queue = Queue::fake();

        /** @var LastfmService|MockInterface $lastfm */
        $lastfm = Mockery::mock(LastfmService::class, ['enabled' => true]);

        (new LoveTrackOnLastfm($lastfm))->handle(new SongLikeToggled($interaction, $user));

        $queue->assertPushed(
            LoveTrackOnLastfmJob::class,
            static function (LoveTrackOnLastfmJob $job) use ($interaction, $user): bool {
                static::assertSame($interaction, static::getNonPublicProperty($job, 'interaction'));
                static::assertSame($user, static::getNonPublicProperty($job, 'user'));

                return true;
            }
        );
    }
}
