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
        /** @var Folder $folder */
        $folder = Folder::factory()->create(['path' => 'foo']);

        /** @var Folder $subfolder */
        $subfolder = Folder::factory()->for($folder, 'parent')->create(['path' => 'foo/bar']);

        $irrelevantFolder = Folder::factory()->create(['path' => 'foo/baz']);
        Song::factory()->for($irrelevantFolder)->create();

        $songs = Song::factory()->for($subfolder)->count(2)->create()
            ->merge(Song::factory()->for($folder)->count(1)->create());

        $response = $this->postAs('/api/songs/by-folders', [
            'paths' => ['foo', 'foo/bar'],
        ]);

        self::assertEqualsCanonicalizing($response->json('*.id'), $songs->pluck('id')->all());
    }

    #[Test]
    public function resolveWhenOneOfThePathsIsRoot(): void
    {
        /** @var Folder $folder */
        $folder = Folder::factory()->create(['path' => 'foo']);

        $songs = Song::factory()->for($folder)->count(2)->create()
            ->merge(Song::factory()->count(1)->create());

        $response = $this->postAs('/api/songs/by-folders', [
            'paths' => ['', 'foo'],
        ]);

        self::assertEqualsCanonicalizing($response->json('*.id'), $songs->pluck('id')->all());
    }
}
