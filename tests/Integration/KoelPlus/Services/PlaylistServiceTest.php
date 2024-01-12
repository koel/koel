<?php

namespace Tests\Integration\KoelPlus\Services;

use App\Facades\License;
use App\Models\Playlist;
use App\Services\PlaylistService;
use App\Values\SmartPlaylistRuleGroupCollection;
use InvalidArgumentException;
use Tests\TestCase;

use function Tests\create_user;

class PlaylistServiceTest extends TestCase
{
    private PlaylistService $service;

    public function setUp(): void
    {
        parent::setUp();

        License::fakePlusLicense();
        $this->service = app(PlaylistService::class);
    }

    public function testCreatePlaylistWithOwnSongsOnlyOption(): void
    {
        $rules = SmartPlaylistRuleGroupCollection::create([
            [
                'id' => '45368b8f-fec8-4b72-b826-6b295af0da65',
                'rules' => [
                    [
                        'id' => '8cfa8700-fbc0-4078-b175-af31c20a3582',
                        'model' => 'title',
                        'operator' => 'is',
                        'value' => ['foo'],
                    ],
                ],
            ],
        ]);

        $user = create_user();

        $playlist = $this->service->createPlaylist(
            name: 'foo',
            user: $user,
            ruleGroups: $rules,
            ownSongsOnly: true
        );

        self::assertSame('foo', $playlist->name);
        self::assertTrue($user->is($playlist->user));
        self::assertTrue($playlist->own_songs_only);
    }

    public function testOwnSongsOnlyOptionOnlyWorksWithSmartPlaylistsWhenCreate(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('"Own songs only" option only works with smart playlists and Plus license.');

        $this->service->createPlaylist(
            name: 'foo',
            user: create_user(),
            ownSongsOnly: true
        );
    }

    public function testOwnSongsOnlyOptionOnlyWorksWithSmartPlaylistsWhenUpdate(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('"Own songs only" option only works with smart playlists and Plus license.');

        /** @var Playlist */
        $playlist = Playlist::factory()->create();

        $this->service->updatePlaylist(
            playlist: $playlist,
            name: 'foo',
            ownSongsOnly: true
        );
    }
}
