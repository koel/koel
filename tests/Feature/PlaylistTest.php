<?php

namespace Tests\Feature;

use App\Http\Resources\PlaylistResource;
use App\Models\Playlist;
use App\Models\Song;
use App\Values\SmartPlaylistRule;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class PlaylistTest extends TestCase
{
    #[Test]
    public function listing(): void
    {
        $user = create_user();
        Playlist::factory()->for($user)->count(3)->create();

        $this->getAs('api/playlists', $user)
            ->assertJsonStructure(['*' => PlaylistResource::JSON_STRUCTURE])
            ->assertJsonCount(3, '*');
    }

    #[Test]
    public function creatingPlaylist(): void
    {
        $user = create_user();

        $songs = Song::factory(4)->create();

        $this->postAs('api/playlists', [
            'name' => 'Foo Bar',
            'songs' => $songs->modelKeys(),
            'rules' => [],
        ], $user)
            ->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        $playlist = Playlist::query()->latest()->first();

        self::assertSame('Foo Bar', $playlist->name);
        self::assertTrue($playlist->ownedBy($user));
        self::assertNull($playlist->getFolder());
        self::assertEqualsCanonicalizing($songs->modelKeys(), $playlist->playables->modelKeys());
    }

    #[Test]
    public function creatingSmartPlaylist(): void
    {
        $user = create_user();

        $rule = SmartPlaylistRule::make([
            'model' => 'artist.name',
            'operator' => 'is',
            'value' => ['Bob Dylan'],
        ]);

        $this->postAs('api/playlists', [
            'name' => 'Smart Foo Bar',
            'rules' => [
                [
                    'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                    'rules' => [$rule->toArray()],
                ],
            ],
        ], $user)->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        $playlist = Playlist::query()->latest()->first();

        self::assertSame('Smart Foo Bar', $playlist->name);
        self::assertTrue($playlist->ownedBy($user));
        self::assertTrue($playlist->is_smart);
        self::assertCount(1, $playlist->rule_groups);
        self::assertNull($playlist->getFolder());
        self::assertTrue($rule->equals($playlist->rule_groups[0]->rules[0]));
    }

    #[Test]
    public function creatingSmartPlaylistFailsIfSongsProvided(): void
    {
        $this->postAs('api/playlists', [
            'name' => 'Smart Foo Bar',
            'rules' => [
                [
                    'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                    'rules' => [
                        SmartPlaylistRule::make([
                            'model' => 'artist.name',
                            'operator' => 'is',
                            'value' => ['Bob Dylan'],
                        ])->toArray(),
                    ],
                ],
            ],
            'songs' => Song::factory(3)->create()->modelKeys(),
        ])->assertUnprocessable();
    }

    #[Test]
    public function creatingPlaylistWithNonExistentSongsFails(): void
    {
        $this->postAs('api/playlists', [
            'name' => 'Foo Bar',
            'rules' => [],
            'songs' => ['foo'],
        ])
            ->assertUnprocessable();
    }

    #[Test]
    public function updatePlaylistName(): void
    {
        $playlist = Playlist::factory()->create(['name' => 'Foo']);

        $this->putAs("api/playlists/{$playlist->id}", ['name' => 'Bar'], $playlist->user)
            ->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        self::assertSame('Bar', $playlist->refresh()->name);
    }

    #[Test]
    public function nonOwnerCannotUpdatePlaylist(): void
    {
        $playlist = Playlist::factory()->create(['name' => 'Foo']);

        $this->putAs("api/playlists/{$playlist->id}", ['name' => 'Qux'])->assertForbidden();
        self::assertSame('Foo', $playlist->refresh()->name);
    }

    #[Test]
    public function deletePlaylist(): void
    {
        $playlist = Playlist::factory()->create();

        $this->deleteAs("api/playlists/{$playlist->id}", [], $playlist->user);

        self::assertModelMissing($playlist);
    }

    #[Test]
    public function nonOwnerCannotDeletePlaylist(): void
    {
        $playlist = Playlist::factory()->create();

        $this->deleteAs("api/playlists/{$playlist->id}")->assertForbidden();

        self::assertModelExists($playlist);
    }
}
