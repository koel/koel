<?php

namespace Tests\Feature;

use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Interaction;
use App\Models\Podcast;
use App\Models\RadioStation;
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
        $songs = Song::factory()->for($artist)->for(Album::factory()->for($artist))->createMany(10);

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
    public function listNewestEpisodesOfSubscribedPodcastsByPublishDate(): void
    {
        $user = create_user();
        $podcast = Podcast::factory()->hasAttached($user, relationship: 'subscribers')->createOne();
        $older = Song::factory()
            ->asEpisode()
            ->createOne(['podcast_id' => $podcast->id, 'created_at' => now()->subDays(2)]);
        $newer = Song::factory()
            ->asEpisode()
            ->createOne(['podcast_id' => $podcast->id, 'created_at' => now()->subDay()]);
        Song::factory()->asEpisode()->createOne(['created_at' => now()]);

        $this->getAs('api/overview', $user)->assertJsonPath('recently_added_episodes.*.id', [$newer->id, $older->id]);
    }

    #[Test]
    public function listRecentlySubscribedPodcastsFirst(): void
    {
        $user = create_user();
        $earlier = Podcast::factory()->createOne();
        $later = Podcast::factory()->createOne();
        $earlier->subscribers()->attach($user, ['created_at' => now()->subDay()]);
        $later->subscribers()->attach($user, ['created_at' => now()]);
        Podcast::factory()->createOne();

        $this->getAs('api/overview', $user)->assertJsonPath('recently_subscribed_podcasts.*.id', [
            $later->id,
            $earlier->id,
        ]);
    }

    #[Test]
    public function listRecentlyAddedRadioStationsFirst(): void
    {
        $user = create_user();
        $older = RadioStation::factory()->for($user)->createOne(['created_at' => now()->subDay()]);
        $newer = RadioStation::factory()->for($user)->createOne(['created_at' => now()]);

        $this->getAs('api/overview', $user)->assertJsonPath('recently_added_radio_stations.*.id', [
            $newer->id,
            $older->id,
        ]);
    }

    #[Test]
    public function leaveOutPodcastsAndRadioWhenDisabled(): void
    {
        config(['koel.podcasts.enabled' => false, 'koel.radio.enabled' => false]);

        $user = create_user();
        $podcast = Podcast::factory()->hasAttached($user, relationship: 'subscribers')->createOne();
        Song::factory()->asEpisode()->createOne(['podcast_id' => $podcast->id]);
        RadioStation::factory()->for($user)->createOne();

        $this
            ->getAs('api/overview', $user)
            ->assertJsonCount(0, 'recently_added_episodes')
            ->assertJsonCount(0, 'recently_subscribed_podcasts')
            ->assertJsonCount(0, 'recently_added_radio_stations');
    }

    #[Test]
    public function fetchRandomAlbums(): void
    {
        $user = create_user();
        Album::factory()->has(Song::factory())->createMany(3);

        $this
            ->getAs('api/albums/random', $user)
            ->assertJsonCount(3)
            ->assertJsonStructure(['*' => AlbumResource::JSON_STRUCTURE]);
    }

    #[Test]
    public function fetchRandomArtists(): void
    {
        $user = create_user();
        Artist::factory()->has(Album::factory())->createMany(3);

        $this
            ->getAs('api/artists/random', $user)
            ->assertJsonCount(3)
            ->assertJsonStructure(['*' => ArtistResource::JSON_STRUCTURE]);
    }
}
