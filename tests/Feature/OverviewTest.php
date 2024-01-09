<?php

namespace Tests\Feature;

use App\Models\Interaction;
use App\Models\User;
use Tests\TestCase;

class OverviewTest extends TestCase
{
    public function testIndex(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Interaction::factory(20)->for($user)->create();

        $this->getAs('api/overview', $user)
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
