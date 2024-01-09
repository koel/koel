<?php

namespace Tests\Feature;

use App\Models\Interaction;
use App\Models\User;
use Tests\TestCase;

class FavoriteSongTest extends TestCase
{
    public function testIndex(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Interaction::factory(5)->for($user)->create(['liked' => true]);

        $this->getAs('api/songs/favorite', $user)
            ->assertJsonStructure(['*' => SongTest::JSON_STRUCTURE]);
    }
}
