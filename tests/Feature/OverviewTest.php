<?php

namespace Tests\Feature;

use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Interaction;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class OverviewTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne();

        // Create songs by the same artist so that similar_songs has results
        $songs = Song::factory()->for($artist)->createMany(10);

        foreach ($songs as $song) {
            Interaction::factory()->for($user)->for($song)->createOne();
        }

        $this->getAs('api/overview', $user)->assertJsonStructure([
            'most_played_songs' => [0 => SongResource::JSON_STRUCTURE],
            'recently_played_songs' => [0 => SongResource::JSON_STRUCTURE],
            'recently_added_albums' => [0 => AlbumResource::JSON_STRUCTURE],
            'recently_added_artists' => [0 => ArtistResource::JSON_STRUCTURE],
            'recently_added_songs' => [0 => SongResource::JSON_STRUCTURE],
            'most_played_artists' => [0 => ArtistResource::JSON_STRUCTURE],
            'most_played_albums' => [0 => AlbumResource::JSON_STRUCTURE],
            'least_played_songs' => [0 => SongResource::JSON_STRUCTURE],
            'random_albums' => [0 => AlbumResource::JSON_STRUCTURE],
            'random_artists' => [0 => ArtistResource::JSON_STRUCTURE],
            'random_songs' => [0 => SongResource::JSON_STRUCTURE],
            'similar_songs' => [0 => SongResource::JSON_STRUCTURE],
        ]);
    }

    #[Test]
    public function fetchRandomAlbums(): void
    {
        $user = create_user();
        Album::factory()->createMany(3);

        $this->getAs('api/albums/random', $user)->assertJsonStructure(['*' => AlbumResource::JSON_STRUCTURE]);
    }

    #[Test]
    public function fetchRandomArtists(): void
    {
        $user = create_user();
        Artist::factory()->count(3)->has(Album::factory())->create();

        $this->getAs('api/artists/random', $user)->assertJsonStructure(['*' => ArtistResource::JSON_STRUCTURE]);
    }
}
