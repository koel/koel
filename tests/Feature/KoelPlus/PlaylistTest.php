<?php

namespace Tests\Feature\KoelPlus;

use App\Facades\License;
use App\Models\Playlist;
use App\Values\SmartPlaylistRule;
use Tests\Feature\PlaylistTest as BasePlaylistTest;
use Tests\TestCase;

use function Tests\create_user;

class PlaylistTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        License::fakePlusLicense();
    }

    public function testCreatingPlaylistWithOwnSongsOnlyOption(): void
    {
        $user = create_user();

        $rule = SmartPlaylistRule::make([
            'model' => 'artist.name',
            'operator' => SmartPlaylistRule::OPERATOR_IS,
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
        ], $user)->assertJsonStructure(BasePlaylistTest::JSON_STRUCTURE);

        /** @var Playlist $playlist */
        $playlist = Playlist::query()->orderByDesc('id')->first();

        self::assertSame('Smart Foo Bar', $playlist->name);
        self::assertTrue($playlist->user->is($user));
        self::assertTrue($playlist->is_smart);
        self::assertCount(1, $playlist->rule_groups);
        self::assertNull($playlist->folder_id);
        self::assertTrue($rule->equals($playlist->rule_groups[0]->rules[0]));
        self::assertTrue($playlist->own_songs_only);
    }

    public function testUpdatePlaylistWithOwnSongsOnlyOption(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->smart()->create();

        $this->putAs('api/playlists/' . $playlist->id, [
            'name' => 'Foo',
            'own_songs_only' => true,
            'rules' => $playlist->rules->toArray(),
        ], $playlist->user)
            ->assertJsonStructure(BasePlaylistTest::JSON_STRUCTURE);

        $playlist->refresh();

        self::assertTrue($playlist->own_songs_only);
    }
}
