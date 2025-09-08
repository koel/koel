<?php

namespace Tests\Feature;

use App\Http\Resources\PlaylistResource;
use App\Models\Playlist;
use App\Models\Song;
use App\Values\SmartPlaylist\SmartPlaylistRule;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;
use function Tests\create_playlists;
use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class PlaylistTest extends TestCase
{
    #[Test]
    public function listing(): void
    {
        $user = create_user();
        create_playlists(count: 3, owner: $user);

        $this->getAs('api/playlists', $user)
            ->assertJsonStructure([0 => PlaylistResource::JSON_STRUCTURE])
            ->assertJsonCount(3, '*');
    }

    #[Test]
    public function creatingPlaylist(): void
    {
        $user = create_user();

        $songs = Song::factory(2)->create();

        $this->postAs('api/playlists', [
            'name' => 'Foo Bar',
            'description' => 'Foo Bar Description',
            'songs' => $songs->modelKeys(),
            'rules' => [],
            'cover' => minimal_base64_encoded_image(),
        ], $user)
            ->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        $playlist = Playlist::query()->latest()->first();

        self::assertSame('Foo Bar', $playlist->name);
        self::assertSame('Foo Bar Description', $playlist->description);
        self::assertTrue($playlist->ownedBy($user));
        self::assertNull($playlist->getFolder());
        self::assertEqualsCanonicalizing($songs->modelKeys(), $playlist->playables->modelKeys());
        self::assertNotNull($playlist->cover);
    }

    #[Test]
    public function createPlaylistWithoutCover(): void
    {
        $user = create_user();

        $this->postAs('api/playlists', [
            'name' => 'Foo Bar',
            'description' => 'Foo Bar Description',
            'songs' => [],
            'rules' => [],
        ], $user)
            ->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        $playlist = Playlist::query()->latest()->first();

        self::assertSame('Foo Bar', $playlist->name);
        self::assertSame('Foo Bar Description', $playlist->description);
        self::assertTrue($playlist->ownedBy($user));
        self::assertNull($playlist->getFolder());
        self::assertNull($playlist->cover);
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
            'description' => 'Smart Foo Bar Description',
            'rules' => [
                [
                    'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                    'rules' => [$rule->toArray()],
                ],
            ],
            'cover' => minimal_base64_encoded_image(),
        ], $user)->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        $playlist = Playlist::query()->latest()->first();

        self::assertSame('Smart Foo Bar', $playlist->name);
        self::assertSame('Smart Foo Bar Description', $playlist->description);
        self::assertTrue($playlist->ownedBy($user));
        self::assertTrue($playlist->is_smart);
        self::assertCount(1, $playlist->rule_groups);
        self::assertNull($playlist->getFolder());
        self::assertTrue($rule->equals($playlist->rule_groups[0]->rules[0]));
        self::assertNotNull($playlist->cover);
    }

    #[Test]
    public function creatingSmartPlaylistFailsIfSongsProvided(): void
    {
        $this->postAs('api/playlists', [
            'name' => 'Smart Foo Bar',
            'description' => 'Smart Foo Bar Description',
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
            'songs' => Song::factory(2)->create()->modelKeys(),
        ])->assertUnprocessable();
    }

    #[Test]
    public function creatingPlaylistWithNonExistentSongsFails(): void
    {
        $this->postAs('api/playlists', [
            'name' => 'Foo Bar',
            'description' => 'Foo Bar Description',
            'rules' => [],
            'songs' => ['foo'],
        ])
            ->assertUnprocessable();
    }

    #[Test]
    public function update(): void
    {
        $playlist = create_playlist(['name' => 'Foo']);

        $this->putAs("api/playlists/{$playlist->id}", [
            'name' => 'Bar',
            'description' => 'Bar Description',
        ], $playlist->owner)
            ->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        self::assertSame('Bar', $playlist->refresh()->name);
        self::assertSame('Bar Description', $playlist->description);
    }

    #[Test]
    public function nonOwnerCannotUpdatePlaylist(): void
    {
        $playlist = create_playlist(['name' => 'Foo']);

        $this->putAs("api/playlists/{$playlist->id}", [
            'name' => 'Qux',
            'description' => 'Qux Description',
        ])->assertForbidden();

        self::assertSame('Foo', $playlist->refresh()->name);
    }

    #[Test]
    public function deletePlaylist(): void
    {
        $playlist = create_playlist();

        $this->deleteAs("api/playlists/{$playlist->id}", [], $playlist->owner);

        $this->assertModelMissing($playlist);
    }

    #[Test]
    public function nonOwnerCannotDeletePlaylist(): void
    {
        $playlist = create_playlist();

        $this->deleteAs("api/playlists/{$playlist->id}")->assertForbidden();

        $this->assertModelExists($playlist);
    }
}
