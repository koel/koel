<?php

namespace Tests\Integration\Services;

use App\Enums\Placement;
use App\Helpers\Ulid;
use App\Models\PlaylistFolder;
use App\Models\Podcast;
use App\Models\Song;
use App\Services\PlaylistService;
use App\Values\Playlist\PlaylistCreateData;
use App\Values\Playlist\PlaylistUpdateData;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;
use Tests\TestCase;

use function Tests\create_playlist;
use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class PlaylistServiceTest extends TestCase
{
    private PlaylistService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlaylistService::class);
    }

    #[Test]
    public function createPlaylist(): void
    {
        $user = create_user();

        $data = PlaylistCreateData::make(
            name: 'foo',
            description: 'bar',
            cover: minimal_base64_encoded_image(),
        );

        $playlist = $this->service->createPlaylist($data, $user);

        self::assertSame('foo', $playlist->name);
        self::assertSame('bar', $playlist->description);
        self::assertTrue($user->is($playlist->owner));
        self::assertFalse($playlist->is_smart);
        self::assertNotNull($playlist->cover);
    }

    #[Test]
    public function createPlaylistWithSongs(): void
    {
        $songs = Song::factory(2)->create();
        $user = create_user();

        $data = PlaylistCreateData::make(
            name: 'foo',
            description: 'bar',
            cover: minimal_base64_encoded_image(),
            playableIds: $songs->modelKeys(),
        );

        $playlist = $this->service->createPlaylist($data, $user);

        self::assertSame('foo', $playlist->name);
        self::assertSame('bar', $playlist->description);
        self::assertTrue($user->is($playlist->owner));
        self::assertFalse($playlist->is_smart);
        self::assertEqualsCanonicalizing($playlist->playables->modelKeys(), $songs->modelKeys());
    }

    #[Test]
    public function createPlaylistWithoutCover(): void
    {
        $user = create_user();

        $data = PlaylistCreateData::make(
            name: 'foo',
            description: 'bar',
        );

        $playlist = $this->service->createPlaylist($data, $user);

        self::assertSame('foo', $playlist->name);
        self::assertSame('bar', $playlist->description);
        self::assertTrue($user->is($playlist->owner));
        self::assertFalse($playlist->is_smart);
        self::assertNull($playlist->cover);
    }

    #[Test]
    public function createSmartPlaylist(): void
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

        $data = PlaylistCreateData::make(
            name: 'foo',
            description: 'bar',
            ruleGroups: $rules,
        );

        $playlist = $this->service->createPlaylist($data, $user);

        self::assertSame('foo', $playlist->name);
        self::assertSame('bar', $playlist->description);
        self::assertTrue($user->is($playlist->owner));
        self::assertTrue($playlist->is_smart);
    }

    #[Test]
    public function createPlaylistInFolder(): void
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create();

        $data = PlaylistCreateData::make(
            name: 'foo',
            description: 'bar',
            folderId: $folder->id,
        );

        $playlist = $this->service->createPlaylist($data, $folder->user);

        self::assertSame('foo', $playlist->name);
        self::assertSame('bar', $playlist->description);
        self::assertTrue($folder->ownedBy($playlist->owner));
        self::assertTrue($playlist->inFolder($folder));
    }

    #[Test]
    public function updateSimplePlaylist(): void
    {
        $playlist = create_playlist(['name' => 'foo']);

        $this->service->updatePlaylist($playlist, PlaylistUpdateData::make(
            name: 'bar',
            description: 'baz',
        ));

        self::assertSame('bar', $playlist->name);
        self::assertSame('baz', $playlist->description);
    }

    #[Test]
    public function updateSmartPlaylist(): void
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

        $playlist = create_playlist(['name' => 'foo', 'rules' => $rules]);

        $data = PlaylistUpdateData::make(
            name: 'bar',
            description: 'baz',
            ruleGroups: SmartPlaylistRuleGroupCollection::create([
                [
                    'id' => '45368b8f-fec8-4b72-b826-6b295af0da65',
                    'rules' => [
                        [
                            'id' => '8cfa8700-fbc0-4078-b175-af31c20a3582',
                            'model' => 'title',
                            'operator' => 'is',
                            'value' => ['bar'],
                        ],
                    ],
                ],
            ]),
        );

        $this->service->updatePlaylist($playlist, $data);

        $playlist->refresh();

        self::assertSame('bar', $playlist->name);
        self::assertSame('baz', $playlist->description);
        self::assertTrue($playlist->is_smart);
        self::assertSame($playlist->rule_groups->first()->rules->first()->value, ['bar']);
    }

    #[Test]
    public function addSongsToPlaylist(): void
    {
        $playlist = create_playlist();
        $playlist->addPlayables(Song::factory(2)->create());
        $songs = Song::factory(2)->create();

        $addedSongs = $this->service->addPlayablesToPlaylist($playlist, $songs, $playlist->owner);
        $playlist->refresh();

        self::assertCount(2, $addedSongs);
        self::assertCount(4, $playlist->playables);
        self::assertEqualsCanonicalizing($addedSongs->modelKeys(), $songs->modelKeys());
        $songs->each(static fn (Song $song) => self::assertTrue($playlist->playables->contains($song)));
    }

    #[Test]
    public function addEpisodesToPlaylist(): void
    {
        $playlist = create_playlist();
        $playlist->addPlayables(Song::factory(2)->create());

        /** @var Podcast $podcast */
        $podcast = Podcast::factory()->create();
        $episodes = Song::factory(2)->asEpisode()->for($podcast)->create();

        $playlist->owner->subscribeToPodcast($podcast);

        $addedEpisodes = $this->service->addPlayablesToPlaylist($playlist, $episodes, $playlist->owner);
        $playlist->refresh();

        self::assertCount(2, $addedEpisodes);
        self::assertCount(4, $playlist->playables);
        self::assertEqualsCanonicalizing($addedEpisodes->modelKeys(), $episodes->modelKeys());
    }

    #[Test]
    public function addMixOfSongsAndEpisodesToPlaylist(): void
    {
        $playlist = create_playlist();
        $playlist->addPlayables(Song::factory(2)->create());
        $playables = Song::factory(2)->asEpisode()->create()
            ->merge(Song::factory(2)->create());

        $addedEpisodes = $this->service->addPlayablesToPlaylist($playlist, $playables, $playlist->owner);
        $playlist->refresh();

        self::assertCount(4, $addedEpisodes);
        self::assertCount(6, $playlist->playables);
        self::assertEqualsCanonicalizing($addedEpisodes->modelKeys(), $playables->modelKeys());
    }

    #[Test]
    public function privateSongsAreMadePublicWhenAddedToCollaborativePlaylist(): void
    {
        PlusTestCase::enablePlusLicense();

        $playlist = create_playlist();
        $user = create_user();
        $playlist->collaborators()->attach($user);
        $playlist->refresh();
        self::assertTrue($playlist->is_collaborative);

        $songs = Song::factory(2)->create(['is_public' => false]);

        $this->service->addPlayablesToPlaylist($playlist, $songs, $user);

        $songs->each(static fn (Song $song) => self::assertTrue($song->refresh()->is_public));
    }

    #[Test]
    public function makePlaylistSongsPublic(): void
    {
        $playlist = create_playlist();
        $playlist->addPlayables(Song::factory(2)->create(['is_public' => false]));

        $this->service->makePlaylistContentPublic($playlist);

        $playlist->playables->each(static fn (Song $song) => self::assertTrue($song->is_public));
    }

    #[Test]
    public function moveSongsInPlaylist(): void
    {
        $playlist = create_playlist();
        $songs = Song::factory(4)->create();
        $ids = $songs->modelKeys();
        $playlist->addPlayables($songs);

        $this->service->movePlayablesInPlaylist($playlist, [$ids[2], $ids[3]], $ids[0], Placement::AFTER);
        self::assertSame([$ids[0], $ids[2], $ids[3], $ids[1]], $playlist->refresh()->playables->modelKeys());

        $this->service->movePlayablesInPlaylist($playlist, [$ids[0]], $ids[3], Placement::BEFORE);
        self::assertSame([$ids[2], $ids[0], $ids[3], $ids[1]], $playlist->refresh()->playables->modelKeys());

        // move to the first position
        $this->service->movePlayablesInPlaylist($playlist, [$ids[0], $ids[1]], $ids[2], Placement::BEFORE);
        self::assertSame([$ids[0], $ids[1], $ids[2], $ids[3]], $playlist->refresh()->playables->modelKeys());

        // move to the last position
        $this->service->movePlayablesInPlaylist($playlist, [$ids[0], $ids[1]], $ids[3], Placement::AFTER);
        self::assertSame([$ids[2], $ids[3], $ids[0], $ids[1]], $playlist->refresh()->playables->modelKeys());
    }

    #[Test]
    public function updatePlaylistCover(): void
    {
        File::put(image_storage_path('foo.webp'), 'content');
        $playlist = create_playlist(['cover' => 'foo.webp']);

        $ulid = Ulid::freeze();
        $this->service->updatePlaylistCover($playlist, minimal_base64_encoded_image());

        self::assertSame(image_storage_url("$ulid.webp"), $playlist->cover);
        self::assertFileExists(image_storage_path("$ulid.webp"));
        self::assertFileDoesNotExist(image_storage_path('foo.webp'));
    }

    #[Test]
    public function deletePlaylistCover(): void
    {
        File::put(image_storage_path('foo.webp'), 'content');

        $playlist = create_playlist(['cover' => 'foo.webp']);

        $this->service->deletePlaylistCover($playlist);

        self::assertNull($playlist->refresh()->cover);
        self::assertFileDoesNotExist(image_storage_path('foo.webp'));
    }
}
