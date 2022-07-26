<?php

namespace Tests\Feature\V6;

use App\Models\Interaction;
use App\Models\User;

class OverviewTest extends TestCase
{
    public function testIndex(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Interaction::factory(20)->create([
            'user_id' => $user->id,
        ]);

        $this->getAsUser('api/overview', $user)
            ->assertJsonStructure([
                'most_played_songs' => ['*' => SongTest::JSON_STRUCTURE],
                'recently_played_songs' => ['*' => SongTest::JSON_STRUCTURE],
                'recently_added_albums' => ['*' => AlbumTest::JSON_STRUCTURE],
                'recently_added_songs' => ['*' => SongTest::JSON_STRUCTURE],
                'most_played_artists' => ['*' => ArtistTest::JSON_STRUCTURE],
                'most_played_albums' => ['*' => AlbumTest::JSON_STRUCTURE],
            ]);
    }
}
