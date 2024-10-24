<?php

namespace Tests\Integration\KoelPlus\Services;

use App\Models\Playlist;
use App\Services\PlaylistService;
use App\Values\SmartPlaylistRuleGroupCollection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlaylistServiceTest extends PlusTestCase
{
    private PlaylistService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlaylistService::class);
    }

    #[Test]
    public function createPlaylistWithOwnSongsOnlyOption(): void
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

    #[Test]
    public function ownSongsOnlyOptionOnlyWorksWithSmartPlaylistsWhenCreate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"Own songs only" option only works with smart playlists and Plus license.');

        $this->service->createPlaylist(
            name: 'foo',
            user: create_user(),
            ownSongsOnly: true
        );
    }

    #[Test]
    public function ownSongsOnlyOptionOnlyWorksWithSmartPlaylistsWhenUpdate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"Own songs only" option only works with smart playlists and Plus license.');

        /** @var Playlist */
        $playlist = Playlist::factory()->create();

        $this->service->updatePlaylist(
            playlist: $playlist,
            name: 'foo',
            ownSongsOnly: true
        );
    }
}
