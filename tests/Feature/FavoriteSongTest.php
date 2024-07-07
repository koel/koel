<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Interaction;
use Tests\TestCase;

use function Tests\create_user;

class FavoriteSongTest extends TestCase
{
    public function testIndex(): void
    {
        $user = create_user();
        Interaction::factory(5)->for($user)->create(['liked' => true]);

        $this->getAs('api/songs/favorite', $user)
            ->assertJsonStructure(['*' => SongResource::JSON_STRUCTURE]);
    }
}
