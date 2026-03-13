<?php

namespace Tests\Integration\Services;

use App\Enums\SongStorageType;
use App\Models\Folder;
use App\Models\Setting;
use App\Models\Song;
use App\Services\MediaBrowser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaBrowserTest extends TestCase
{
    private MediaBrowser $browser;

    public function setUp(): void
    {
        parent::setUp();

        $this->browser = app(MediaBrowser::class);
    }

    #[Test]
    public function createFolderStructureForSong(): void
    {
        Setting::set('media_path', $this->mediaPath);
        $song = Song::factory()->createOne([
            'path' => "$this->mediaPath/foo/bar/baz.mp3",
            'storage' => SongStorageType::LOCAL,
        ]);

        self::assertNull($song->folder);

        $this->browser->maybeCreateFolderStructureForSong($song);
        /** @var Folder $foo */
        $foo = Folder::query()->where('path', 'foo')->first();

        /** @var Folder $bar */
        $bar = Folder::query()->where(['path' => 'foo/bar', 'parent_id' => $foo->id])->first();

        self::assertTrue($song->folder->is($bar));

        // Make sure subsequent runs work too
        $qux = Song::factory()->createOne([
            'path' => "$this->mediaPath/foo/bar/qux.mp3",
            'storage' => SongStorageType::LOCAL,
        ]);

        $this->browser->maybeCreateFolderStructureForSong($qux);

        self::assertTrue($qux->folder->is($bar));

        $songInRootPath = Song::factory()->createOne([
            'path' => "$this->mediaPath/baz.mp3",
            'storage' => SongStorageType::LOCAL,
        ]);

        $this->browser->maybeCreateFolderStructureForSong($songInRootPath);

        self::assertNull($songInRootPath->folder);
    }
}
