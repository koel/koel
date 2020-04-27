<?php

namespace App\Tests\Unit\Jobs;

use App\Jobs\ScrobbleJob;
use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Mockery;
use Tests\TestCase;

class ScrobbleJobTest extends TestCase
{
    /** @var ScrobbleJob */
    private $job;

    /** @var User */
    private $user;

    /** @var Song */
    private $song;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create(['preferences' => ['lastfm_session_key' => 'foo']]);
        $this->song = factory(Song::class)->make();
        $this->job = new ScrobbleJob($this->user, $this->song, 100);
    }

    public function testHandle(): void
    {
        $lastFm = Mockery::mock(LastfmService::class);
        $lastFm->shouldReceive('scrobble')
            ->once()
            ->with($this->song->artist->name, $this->song->title, 100, $this->song->album->name, 'foo');

        $this->job->handle($lastFm);
    }
}
