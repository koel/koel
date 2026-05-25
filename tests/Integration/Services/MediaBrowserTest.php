<?php

namespace Tests\Integration\Services;

use App\Enums\SongStorageType;
use App\Models\Folder;
use App\Models\Setting;
use App\Models\Song;
use App\Services\MediaBrowser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

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

    #[Test]
    public function getSubfolderViewReturnsCurrentAncestorsAndSubfoldersWithEagerLoadedUploader(): void
    {
        $this->actingAs(create_user());

        $music = Folder::factory()->createOne(['path' => 'Music']);
        $rock = Folder::factory()->for($music, 'parent')->createOne(['path' => 'Music/Rock']);
        Folder::factory()->for($rock, 'parent')->createOne(['path' => 'Music/Rock/PinkFloyd']);
        Folder::factory()->for($rock, 'parent')->createOne(['path' => 'Music/Rock/LedZeppelin']);

        $view = $this->browser->getSubfolderView($rock);

        self::assertTrue($view['current']->is($rock));
        self::assertSame([$music->id], $view['ancestors']->pluck('id')->all());
        self::assertCount(2, $view['subfolders']);

        self::assertTrue($view['current']->relationLoaded('uploader'));
        $view['ancestors']->each(static fn (Folder $f) => self::assertTrue($f->relationLoaded('uploader')));
        $view['subfolders']->each(static fn (Folder $f) => self::assertTrue($f->relationLoaded('uploader')));
    }

    #[Test]
    public function getSubfolderViewReturnsRootLevelSubfoldersWithNullCurrent(): void
    {
        $this->actingAs(create_user());

        Folder::factory()->createMany(3);

        $view = $this->browser->getSubfolderView(null);

        self::assertNull($view['current']);
        self::assertTrue($view['ancestors']->isEmpty());
        self::assertCount(3, $view['subfolders']);
    }

    #[Test]
    public function skipsSongOutsideMediaPath(): void
    {
        Setting::set('media_path', $this->mediaPath);

        // Simulates a song whose path predates a media_path normalization fix, or any path
        // that doesn't sit under the configured media_path. The folder structure must NOT
        // be created — otherwise the media browser would expose host directories.
        $song = Song::factory()->createOne([
            'path' => '/etc/passwd-themed/album/track.mp3',
            'storage' => SongStorageType::LOCAL,
        ]);

        $this->browser->maybeCreateFolderStructureForSong($song);

        self::assertNull($song->folder);
        self::assertSame(0, Folder::query()->where('path', 'etc')->count());
        self::assertSame(0, Folder::query()->where('path', 'etc/passwd-themed')->count());
    }
}
