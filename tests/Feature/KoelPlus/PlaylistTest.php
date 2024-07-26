<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\PlaylistResource;
use App\Models\Playlist;
use App\Values\SmartPlaylistRule;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlaylistTest extends PlusTestCase
{
    public function testCreatingPlaylistWithOwnSongsOnlyOption(): void
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
            'own_songs_only' => true,
        ], $user)->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        /** @var Playlist $playlist */
        $playlist = Playlist::query()->latest()->first();

        self::assertSame('Smart Foo Bar', $playlist->name);
        self::assertTrue($playlist->ownedBy($user));
        self::assertTrue($playlist->is_smart);
        self::assertCount(1, $playlist->rule_groups);
        self::assertNull($playlist->getFolderId());
        self::assertTrue($rule->equals($playlist->rule_groups[0]->rules[0]));
        self::assertTrue($playlist->own_songs_only);
    }

    public function testUpdatePlaylistWithOwnSongsOnlyOption(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->smart()->create();

        $this->putAs("api/playlists/$playlist->id", [
            'name' => 'Foo',
            'own_songs_only' => true,
            'rules' => $playlist->rules->toArray(),
        ], $playlist->user)
            ->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        $playlist->refresh();

        self::assertTrue($playlist->own_songs_only);
    }

    public function testCollaboratorCannotChangePlaylistName(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this->putAs("api/playlists/$playlist->id", ['name' => 'Nope'], $collaborator)
            ->assertForbidden();
    }
}
