<?php

namespace Tests\Feature;

use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\PodcastResource;
use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Podcast;
use App\Models\Song;
use Tests\TestCase;

use function Tests\create_user;

class ExcerptSearchTest extends TestCase
{
    public function testSearch(): void
    {
        Song::factory()->create(['title' => 'A Foo Song']);
        Song::factory(6)->create();

        Artist::factory()->create(['name' => 'Foo Fighters']);
        Artist::factory(3)->create();

        Album::factory()->create(['name' => 'Foo Number Five']);
        Album::factory(4)->create();

        $user = create_user();
        /** @var Podcast $podcast */
        $podcast = Podcast::factory()->create(['title' => 'Foo Podcast']);
        $user->subscribeToPodcast($podcast);

        $this->getAs('api/search?q=foo', $user)
            ->assertJsonStructure([
                'songs' => ['*' => SongResource::JSON_STRUCTURE],
                'podcasts' => ['*' => PodcastResource::JSON_STRUCTURE],
                'artists' => ['*' => ArtistResource::JSON_STRUCTURE],
                'albums' => ['*' => AlbumResource::JSON_STRUCTURE],
            ]);
    }
}
