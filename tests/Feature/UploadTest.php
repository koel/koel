<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Song;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_manager;
use function Tests\create_user;
use function Tests\sandbox_path;
use function Tests\test_path;

class UploadTest extends TestCase
{
    private UploadedFile $file;

    public function setUp(): void
    {
        parent::setUp();

        $this->file = UploadedFile::fromFile(test_path('songs/full.mp3'), 'song.mp3'); //@phpstan-ignore-line
    }

    #[Test]
    public function unauthorizedPost(): void
    {
        Setting::set('media_path', sandbox_path('media'));

        $this->postAs('/api/upload', ['file' => $this->file], create_user())->assertForbidden();
    }

    #[Test]
    public function uploadFailsIfMediaPathIsNotSet(): void
    {
        Setting::set('media_path', '');

        $this->postAs('/api/upload', ['file' => $this->file], create_admin())->assertForbidden();
    }

    #[Test]
    public function uploadSuccessful(): void
    {
        Setting::set('media_path', sandbox_path('media'));

        $this->postAs('/api/upload', ['file' => $this->file], create_admin())->assertJsonStructure(['song', 'album']);
    }

    #[Test]
    public function untaggedUploadStoredUnderAClashingNameIsTitledAfterTheUploadedFile(): void
    {
        Setting::set('media_path', sandbox_path('media'));
        $admin = create_admin(['preferences' => ['detect_duplicate_uploads' => false]]);

        for ($i = 0; $i < 2; $i++) {
            $this->postAs(
                '/api/upload',
                ['file' => UploadedFile::fromFile(test_path('songs/blank.mp3'), 'Untagged Song.mp3')], //@phpstan-ignore-line
                $admin,
            )->assertSuccessful();
        }

        self::assertSame(2, Song::query()->where('title', 'Untagged Song')->count());
    }

    #[Test]
    public function uploadDisabledInDemoMode(): void
    {
        config(['koel.misc.demo' => true]);
        Setting::set('media_path', sandbox_path('media'));

        try {
            $this->postAs('/api/upload', ['file' => $this->file], create_manager())->assertForbidden();
        } finally {
            config(['koel.misc.demo' => false]);
        }
    }
}
