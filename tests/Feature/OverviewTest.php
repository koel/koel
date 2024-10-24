<?php

namespace Tests\Feature;

use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\SongResource;
use App\Models\Interaction;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class OverviewTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        $user = create_user();

        Interaction::factory(20)->for($user)->create();

        $this->getAs('api/overview', $user)
            ->assertJsonStructure([
                'most_played_songs' => ['*' => SongResource::JSON_STRUCTURE],
                'recently_played_songs' => ['*' => SongResource::JSON_STRUCTURE],
                'recently_added_albums' => ['*' => AlbumResource::JSON_STRUCTURE],
                'recently_added_songs' => ['*' => SongResource::JSON_STRUCTURE],
                'most_played_artists' => ['*' => ArtistResource::JSON_STRUCTURE],
                'most_played_albums' => ['*' => AlbumResource::JSON_STRUCTURE],
            ]);
    }
}
