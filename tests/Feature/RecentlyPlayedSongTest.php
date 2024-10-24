<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Interaction;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class RecentlyPlayedSongTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        $user = create_user();

        Interaction::factory(5)->for($user)->create();

        $this->getAs('api/songs/recently-played', $user)
            ->assertJsonStructure(['*' => SongResource::JSON_STRUCTURE]);
    }
}
