<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Mockery;

class ScrobbleTest extends TestCase
{
    public function testLastfmScrobble(): void
    {
        $this->withoutEvents();

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Song $song */
        $song = Song::factory()->create();

        self::mock(LastfmService::class)
            ->shouldReceive('scrobble')
            ->with(
                Mockery::on(static fn (Song $s) => $s->is($song)),
                Mockery::on(static fn (User $u) => $u->is($user)),
                100
            )
            ->once();

        $this->postAs("/api/songs/$song->id/scrobble", ['timestamp' => 100], $user)
            ->assertNoContent();
    }
}
