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

        $songs = Song::factory()
            ->for($subfolder)
            ->count(2)
            ->create()
            ->merge(Song::factory()->for($folder)->count(1)->create());

        $response = $this->postAs('/api/songs/by-folders', [
            'folders' => [$folder->id, $subfolder->id],
        ]);

        self::assertEqualsCanonicalizing($response->json('*.id'), $songs->pluck('id')->all());
    }

    #[Test]
    public function fetchSongsForASingleFolder(): void
    {
        $folder = Folder::factory()->createOne(['path' => 'foo']);

        $songs = Song::factory()->for($folder)->count(2)->create();
        // a root-level song that should NOT be returned
        Song::factory()->createOne();

        $response = $this->postAs('/api/songs/by-folders', [
            'folders' => [$folder->id],
        ]);

        self::assertEqualsCanonicalizing($response->json('*.id'), $songs->pluck('id')->all());
    }
}
