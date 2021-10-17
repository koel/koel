<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Values\SmartPlaylistRule;
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

        $response = $this->postAsUser('api/playlist', [
            'name' => 'Foo Bar',
            'songs' => $songs->pluck('id')->toArray(),
            'rules' => [],
        ], $user);

        $response->assertOk();

        /** @var Playlist $playlist */
        $playlist = Playlist::orderBy('id', 'desc')->first();

        self::assertSame('Foo Bar', $playlist->name);
        self::assertTrue($playlist->user->is($user));
        self::assertEqualsCanonicalizing($songs->pluck('id')->all(), $playlist->songs->pluck('id')->all());
    }

    public function testCreatingSmartPlaylist(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $rule = SmartPlaylistRule::create([
            'model' => 'artist.name',
            'operator' => SmartPlaylistRule::OPERATOR_IS,
            'value' => ['Bob Dylan'],
        ]);

        $this->postAsUser('api/playlist', [
            'name' => 'Smart Foo Bar',
            'rules' => [
                [
                    'id' => 12345,
                    'rules' => [$rule->toArray()],
                ],
            ],
        ], $user);

        /** @var Playlist $playlist */
        $playlist = Playlist::orderBy('id', 'desc')->first();

        self::assertSame('Smart Foo Bar', $playlist->name);
        self::assertTrue($playlist->user->is($user));
        self::assertTrue($playlist->is_smart);
        self::assertCount(1, $playlist->rule_groups);
        self::assertTrue($rule->equals($playlist->rule_groups[0]->rules[0]));
    }

    public function testCreatingSmartPlaylistIgnoresSongs(): void
    {
        $this->postAsUser('api/playlist', [
            'name' => 'Smart Foo Bar',
            'rules' => [
                [
                    'id' => 12345,
                    'rules' => [
                        SmartPlaylistRule::create([
                            'model' => 'artist.name',
                            'operator' => SmartPlaylistRule::OPERATOR_IS,
                            'value' => ['Bob Dylan'],
                        ])->toArray(),
                    ],
                ],
            ],
            'songs' => Song::orderBy('id')->take(3)->get()->pluck('id')->all(),
        ]);

        /** @var Playlist $playlist */
        $playlist = Playlist::orderBy('id', 'desc')->first();

        self::assertSame('Smart Foo Bar', $playlist->name);
        self::assertEmpty($playlist->songs);
    }

    public function testCreatingPlaylistWithNonExistentSongsFails(): void
    {
        $response = $this->postAsUser('api/playlist', [
            'name' => 'Foo Bar',
            'rules' => [],
            'songs' => ['foo'],
        ]);

        $response->assertUnprocessable();
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

        $this->putAsUser("api/playlist/$playlist->id", ['name' => 'Bar'], $user);

        self::assertSame('Bar', $playlist->refresh()->name);
    }

    public function testNonOwnerCannotUpdatePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'name' => 'Foo',
        ]);

        $response = $this->putAsUser("api/playlist/$playlist->id", ['name' => 'Qux']);
        $response->assertStatus(403);
    }

    public function testDeletePlaylist(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->deleteAsUser("api/playlist/$playlist->id", [], $user);
        self::assertDatabaseMissing('playlists', ['id' => $playlist->id]);
    }

    public function testNonOwnerCannotDeletePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $this->deleteAsUser("api/playlist/$playlist->id")
            ->assertStatus(403);
    }
}
