<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\Rule;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Collection;

class PlaylistTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        static::createSampleMediaSet();
    }

    public function testCreatingPlaylist(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var array<Song>|Collection $songs */
        $songs = Song::orderBy('id')->take(3)->get();

        $this->postAsUser('api/playlist', [
            'name' => 'Foo Bar',
            'songs' => $songs->pluck('id')->toArray(),
            'rules' => [],
        ], $user);

        self::assertDatabaseHas('playlists', [
            'user_id' => $user->id,
            'name' => 'Foo Bar',
        ]);

        /** @var Playlist $playlist */
        $playlist = Playlist::orderBy('id', 'desc')->first();

        foreach ($songs as $song) {
            self::assertDatabaseHas('playlist_song', [
                'playlist_id' => $playlist->id,
                'song_id' => $song->id,
            ]);
        }
    }

    public function testCreatingSmartPlaylist(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $rule = Rule::create([
            'model' => 'artist.name',
            'operator' => Rule::OPERATOR_IS,
            'value' => 'Bob Dylan',
        ]);

        $this->postAsUser('api/playlist', [
            'name' => 'Smart Foo Bar',
            'rules' => [$rule->toArray()],
        ], $user);

        /** @var Playlist $playlist */
        $playlist = Playlist::orderBy('id', 'desc')->first();

        self::assertSame('Smart Foo Bar', $playlist->name);
        self::assertTrue($playlist->user->is($user));
        self::assertTrue($playlist->is_smart);
        self::assertCount(1, $playlist->rules);
        self::assertTrue(Rule::create($playlist->rules[0])->equals($rule));
    }

    public function testCreatingSmartPlaylistIgnoresSongs(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->postAsUser('api/playlist', [
            'name' => 'Smart Foo Bar',
            'rules' => [
                Rule::create([
                    'model' => 'artist.name',
                    'operator' => Rule::OPERATOR_IS,
                    'value' => 'Bob Dylan',
                ])->toArray(),
            ],
            'songs' => Song::orderBy('id')->take(3)->get()->pluck('id')->toArray(),
        ], $user);

        /** @var Playlist $playlist */
        $playlist = Playlist::orderBy('id', 'desc')->first();

        self::assertSame('Smart Foo Bar', $playlist->name);
        self::assertEmpty($playlist->songs);
    }

    public function testUpdatePlaylistName(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'user_id' => $user->id,
            'name' => 'Foo',
        ]);

        $this->putAsUser("api/playlist/{$playlist->id}", ['name' => 'Bar'], $user);

        self::assertSame('Bar', $playlist->refresh()->name);
    }

    public function testNonOwnerCannotUpdatePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'name' => 'Foo',
        ]);

        $response = $this->putAsUser("api/playlist/{$playlist->id}", ['name' => 'Qux']);
        $response->assertStatus(403);
    }

    public function testSyncPlaylist(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'user_id' => $user->id,
        ]);

        /** @var array<Song>|Collection $songs */
        $songs = Song::orderBy('id')->take(4)->get();
        $playlist->songs()->attach($songs->pluck('id')->toArray());

        /** @var Song $removedSong */
        $removedSong = $songs->pop();

        $this->putAsUser("api/playlist/{$playlist->id}/sync", [
            'songs' => $songs->pluck('id')->toArray(),
        ], $user);

        // We should still see the first 3 songs, but not the removed one
        foreach ($songs as $song) {
            self::assertDatabaseHas('playlist_song', [
                'playlist_id' => $playlist->id,
                'song_id' => $song->id,
            ]);
        }

        self::assertDatabaseMissing('playlist_song', [
            'playlist_id' => $playlist->id,
            'song_id' => $removedSong->id,
        ]);
    }

    public function testDeletePlaylist(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->deleteAsUser("api/playlist/{$playlist->id}", [], $user);
        self::assertDatabaseMissing('playlists', ['id' => $playlist->id]);
    }

    public function testNonOwnerCannotDeletePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $this->deleteAsUser("api/playlist/{$playlist->id}")
            ->assertStatus(403);
    }

    public function testGetPlaylist(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'user_id' => $user->id,
        ]);

        $songs = Song::factory(2)->create();
        $playlist->songs()->saveMany($songs);

        $this->getAsUser("api/playlist/{$playlist->id}/songs", $user)
            ->assertJson($songs->pluck('id')->all());
    }
}
