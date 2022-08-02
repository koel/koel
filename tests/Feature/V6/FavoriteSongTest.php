<?php

namespace Tests\Feature\V6;

use App\Models\Interaction;
use App\Models\User;

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
