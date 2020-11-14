<?php

namespace Tests\Unit\Jobs;

use App\Jobs\LoveTrackOnLastfmJob;
use App\Models\Interaction;
use App\Models\User;
use App\Services\LastfmService;
use Mockery;
use Tests\TestCase;

class LoveTrackOnLastfmJobTest extends TestCase
{
    private $job;
    private $user;
    private $interaction;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['preferences' => ['lastfm_session_key' => 'foo']]);
        $this->interaction = Interaction::factory()->make();
        $this->job = new LoveTrackOnLastfmJob($this->user, $this->interaction);
    }

    public function testHandle(): void
    {
        $lastFm = Mockery::mock(LastfmService::class);
        $lastFm->shouldReceive('toggleLoveTrack')
            ->once()
            ->with(
                $this->interaction->song->title,
                $this->interaction->song->artist->name,
                'foo',
                $this->interaction->liked
            );

        $this->job->handle($lastFm);
    }
}
