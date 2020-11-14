<?php

namespace Tests\Integration\Listeners;

use App\Events\SongStartedPlaying;
use App\Jobs\UpdateLastfmNowPlayingJob;
use App\Listeners\UpdateLastfmNowPlaying;
use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\Feature\TestCase;

class UpdateLastfmNowPlayingTest extends TestCase
{
    public function testUpdateNowPlayingStatus(): void
    {
        static::createSampleMediaSet();

        $user = User::factory()->create(['preferences' => ['lastfm_session_key' => 'bar']]);
        $song = Song::first();

        $queue = Queue::fake();

        /** @var LastfmService|MockInterface $lastfm */
        $lastfm = Mockery::mock(LastfmService::class, ['enabled' => true]);

        (new UpdateLastfmNowPlaying($lastfm))->handle(new SongStartedPlaying($song, $user));

        $queue->assertPushed(
            UpdateLastfmNowPlayingJob::class,
            static function (UpdateLastfmNowPlayingJob $job) use ($user, $song): bool {
                self::assertSame($user, static::getNonPublicProperty($job, 'user'));
                self::assertSame($song, static::getNonPublicProperty($job, 'song'));

                return true;
            }
        );
    }
}
