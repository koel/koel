<?php

namespace Tests\Feature\KoelPlus\MediaBrowser;

use App\Models\Folder;
use App\Models\Setting;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class FetchRecursiveFolderSongsTest extends PlusTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Setting::set('media_path', '/var/media');
    }

    #[Test]
    public function includesSongsInNestedFolder(): void
    {
        $folder = Folder::factory()->createOne(['path' => 'foo']);
        $subfolder = Folder::factory()->for($folder, 'parent')->createOne(['path' => 'foo/bar']);

        $irrelevantFolder = Folder::factory()->createOne(['path' => 'foo/baz']);
        Song::factory()->for($irrelevantFolder)->createOne();

        $songs = Song::factory()->for($subfolder)->createMany(2)->push(Song::factory()->for($folder)->createOne());

        $response = $this->postAs('/api/songs/by-folders', [
            'folders' => [$folder->id, $subfolder->id],
        ]);

        self::assertEqualsCanonicalizing($response->json('*.id'), $songs->pluck('id')->all());
    }

    #[Test]
    public function fetchSongsForASingleFolder(): void
    {
        $folder = Folder::factory()->createOne(['path' => 'foo']);

        $folderSongs = Song::factory()->for($folder)->createMany(2);
        $rootLevelSong = Song::factory()->createOne();

        $response = $this->postAs('/api/songs/by-folders', [
            'folders' => [$folder->id],
        ]);

        self::assertEqualsCanonicalizing($response->json('*.id'), $folderSongs->pluck('id')->all());
        self::assertNotContains($rootLevelSong->id, $response->json('*.id'));
    }
}
