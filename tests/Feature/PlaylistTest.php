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
        $songs = Song::query()->orderBy('id')->take(3)->get();

        $response = $this->postAs('api/playlist', [
            'name' => 'Foo Bar',
            'songs' => $songs->pluck('id')->toArray(),
            'rules' => [],
        ], $user);

        $response->assertOk();

        /** @var Playlist $playlist */
        $playlist = Playlist::query()->orderByDesc('id')->first();

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

        $this->postAs('api/playlist', [
            'name' => 'Smart Foo Bar',
            'rules' => [
                [
                    'id' => '45368b8f-fec8-4b72-b826-6b295af0da65',
                    'rules' => [$rule->toArray()],
                ],
            ],
        ], $user);

        /** @var Playlist $playlist */
        $playlist = Playlist::query()->orderByDesc('id')->first();

        self::assertSame('Smart Foo Bar', $playlist->name);
        self::assertTrue($playlist->user->is($user));
        self::assertTrue($playlist->is_smart);
        self::assertCount(1, $playlist->rule_groups);
        self::assertTrue($rule->equals($playlist->rule_groups[0]->rules[0]));
    }

    public function testCreatingPlaylistCannotHaveBothSongsAndRules(): void
    {
        $this->postAs('api/playlist', [
            'name' => 'Smart Foo Bar',
            'rules' => [
                [
                    'id' => '45368b8f-fec8-4b72-b826-6b295af0da65',
                    'rules' => [
                        SmartPlaylistRule::create([
                            'model' => 'artist.name',
                            'operator' => SmartPlaylistRule::OPERATOR_IS,
                            'value' => ['Bob Dylan'],
                        ])->toArray(),
                    ],
                ],
            ],
            'songs' => Song::query()->orderBy('id')->take(3)->get()->pluck('id')->all(),
        ])->assertUnprocessable();
    }

    public function testCreatingPlaylistWithNonExistentSongsFails(): void
    {
        $this->postAs('api/playlist', [
            'name' => 'Foo Bar',
            'rules' => [],
            'songs' => ['foo'],
        ])
            ->assertUnprocessable();
    }

    public function testUpdatePlaylistName(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create(['name' => 'Foo']);

        $this->putAs("api/playlist/$playlist->id", ['name' => 'Bar'], $playlist->user);

        self::assertSame('Bar', $playlist->refresh()->name);
    }

    public function testNonOwnerCannotUpdatePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create(['name' => 'Foo']);

        $this->putAs("api/playlist/$playlist->id", ['name' => 'Qux'])->assertForbidden();
    }

    public function testDeletePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $this->deleteAs("api/playlist/$playlist->id", [], $playlist->user);

        self::assertModelMissing($playlist);
    }

    public function testNonOwnerCannotDeletePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $this->deleteAs("api/playlist/$playlist->id")->assertForbidden();

        self::assertModelExists($playlist);
    }
}
