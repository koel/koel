<?php

namespace Tests\Unit\Jobs;

use App\Jobs\UpdateLastfmNowPlayingJob;
use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Mockery;
use Tests\TestCase;

class UpdateLastfmNowPlayingJobTest extends TestCase
{
    private $job;
    private $user;
    private $song;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['preferences' => ['lastfm_session_key' => 'foo']]);
        $this->song = Song::factory()->make();
        $this->job = new UpdateLastfmNowPlayingJob($this->user, $this->song);
    }

    public function testHandle(): void
    {
        $lastFm = Mockery::mock(LastfmService::class);
        $lastFm->shouldReceive('updateNowPlaying')
            ->once()
            ->with(
                $this->song->artist->name,
                $this->song->title,
                $this->song->album->name,
                $this->song->length,
                'foo'
            );

        $this->job->handle($lastFm);
    }
}
