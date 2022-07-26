<?php

namespace Tests\Feature\V6;

use App\Models\Interaction;
use App\Models\User;

class FavoriteTest extends TestCase
{
    public function testIndex(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Interaction::factory(5)->create([
            'user_id' => $user->id,
        ]);

        $this->getAsUser('api/favorites', $user)
            ->assertJsonStructure(['*' => SongTest::JSON_STRUCTURE]);
    }
}
