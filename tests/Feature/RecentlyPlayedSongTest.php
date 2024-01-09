<?php

namespace Tests\Feature;

use App\Models\Interaction;
use App\Models\User;
use Tests\TestCase;

class RecentlyPlayedSongTest extends TestCase
{
    public function testIndex(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Interaction::factory(5)->for($user)->create();

        $this->getAs('api/songs/recently-played', $user)
            ->assertJsonStructure(['*' => SongTest::JSON_STRUCTURE]);
    }
}
