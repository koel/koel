<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ScrobbleJob;
use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Mockery;
use Tests\TestCase;

class ScrobbleJobTest extends TestCase
{
    public function testHandle(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Song $song */
        $song = Song::factory()->make();

        $job = new ScrobbleJob($user, $song, 100);

        $lastfm = Mockery::mock(LastfmService::class);

        $lastfm->shouldReceive('scrobble')
            ->once()
            ->with($song, $user, 100);

        $job->handle($lastfm);
    }
}
