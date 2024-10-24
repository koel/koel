<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Playlist;
use App\Models\Song;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class PlaylistSongTest extends TestCase
{
    #[Test]
    public function getNormalPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $playlist->addPlayables(Song::factory(5)->create());

        $this->getAs("api/playlists/$playlist->id/songs", $playlist->user)
            ->assertJsonStructure(['*' => SongResource::JSON_STRUCTURE]);
    }

    #[Test]
    public function getSmartPlaylist(): void
    {
        Song::factory()->create(['title' => 'A foo song']);

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'rules' => [
                [
                    'id' => '45368b8f-fec8-4b72-b826-6b295af0da65',
                    'rules' => [
                        [
                            'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                            'model' => 'title',
                            'operator' => 'contains',
                            'value' => ['foo'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->getAs("api/playlists/$playlist->id/songs", $playlist->user)
            ->assertJsonStructure(['*' => SongResource::JSON_STRUCTURE]);
    }

    #[Test]
    public function nonOwnerCannotAccessPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for(create_user())->create();
        $playlist->addPlayables(Song::factory(5)->create());

        $this->getAs("api/playlists/$playlist->id/songs")
            ->assertForbidden();
    }

    #[Test]
    public function addSongsToPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        /** @var Collection<array-key, Song> $songs */
        $songs = Song::factory(2)->create();

        $this->postAs("api/playlists/$playlist->id/songs", ['songs' => $songs->pluck('id')->all()], $playlist->user)
            ->assertSuccessful();

        self::assertEqualsCanonicalizing($songs->pluck('id')->all(), $playlist->playables->pluck('id')->all());
    }

    #[Test]
    public function removeSongsFromPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $toRemainSongs = Song::factory(5)->create();

        /** @var Collection<array-key, Song> $toBeRemovedSongs */
        $toBeRemovedSongs = Song::factory(2)->create();

        $playlist->addPlayables($toRemainSongs->merge($toBeRemovedSongs));

        self::assertCount(7, $playlist->playables);

        $this->deleteAs(
            "api/playlists/$playlist->id/songs",
            ['songs' => $toBeRemovedSongs->pluck('id')->all()],
            $playlist->user
        )
            ->assertNoContent();

        $playlist->refresh();

        self::assertEqualsCanonicalizing($toRemainSongs->pluck('id')->all(), $playlist->playables->pluck('id')->all());
    }

    #[Test]
    public function nonOwnerCannotModifyPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for(create_user())->create();

        /** @var Song $song */
        $song = Song::factory()->create();

        $this->postAs("api/playlists/$playlist->id/songs", ['songs' => [$song->id]])
            ->assertForbidden();

        $this->deleteAs("api/playlists/$playlist->id/songs", ['songs' => [$song->id]])
            ->assertForbidden();
    }

    #[Test]
    public function smartPlaylistContentCannotBeModified(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'rules' => [
                [
                    'id' => '45368b8f-fec8-4b72-b826-6b295af0da65',
                    'rules' => [
                        [
                            'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                            'model' => 'title',
                            'operator' => 'contains',
                            'value' => ['foo'],
                        ],
                    ],
                ],
            ],
        ]);

        $songs = Song::factory(2)->create()->pluck('id')->all();

        $this->postAs("api/playlists/$playlist->id/songs", ['songs' => $songs], $playlist->user)
            ->assertForbidden();

        $this->deleteAs("api/playlists/$playlist->id/songs", ['songs' => $songs], $playlist->user)
            ->assertForbidden();
    }
}
