<?php

namespace Tests\Feature;

use App\Jobs\ScrobbleJob;
use App\Models\Song;
use App\Services\Dispatcher;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ScrobbleTest extends TestCase
{
    #[Test]
    public function lastfmScrobble(): void
    {
        $user = create_user();

        /** @var Song $song */
        $song = Song::factory()->create();

        $this->mock(Dispatcher::class)
            ->expects('dispatch')
            ->andReturnUsing(function (ScrobbleJob $job) use ($song, $user): void {
                $this->assertTrue($song->is($job->song));
                $this->assertTrue($user->is($job->user));
                $this->assertEquals(100, $job->timestamp);
            });

        $this->postAs("/api/songs/{$song->id}/scrobble", ['timestamp' => 100], $user)
            ->assertNoContent();
    }
}
