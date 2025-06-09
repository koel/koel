<?php

namespace Tests\Integration\KoelPlus\Services\SongStorages;

use App\Models\Song;
use App\Services\SongStorages\S3CompatibleStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\test_path;

class S3CompatibleStorageTest extends PlusTestCase
{
    private S3CompatibleStorage $service;
    private UploadedFile $file;

    public function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
        $this->service = app(S3CompatibleStorage::class);
        $this->file = UploadedFile::fromFile(test_path('songs/full.mp3'), 'song.mp3'); //@phpstan-ignore-line
    }

    #[Test]
    public function storeUploadedFile(): void
    {
        self::assertEquals(0, Song::query()->where('storage', 's3')->count());

        $song = $this->service->storeUploadedFile($this->file, create_user());

        Storage::disk('s3')->assertExists($song->storage_metadata->getPath());
        self::assertEquals(1, Song::query()->where('storage', 's3')->count());
    }

    #[Test]
    public function storingWithVisibilityPreference(): void
    {
        $user = create_user();

        $user->preferences->makeUploadsPublic = true;
        $user->save();

        self::assertTrue($this->service->storeUploadedFile($this->file, $user)->is_public);

        $user->preferences->makeUploadsPublic = false;
        $user->save();

        $privateFile = UploadedFile::fromFile(test_path('songs/full.mp3'), 'song.mp3'); //@phpstan-ignore-line
        self::assertFalse($this->service->storeUploadedFile($privateFile, $user)->is_public);
    }

    #[Test]
    public function deleteWithNoBackup(): void
    {
        Storage::disk('s3')->put('full.mp3', 'fake content');

        $this->service->delete('full.mp3');

        Storage::disk('s3')->assertMissing('full.mp3');
    }

    #[Test]
    public function deleteWithBackup(): void
    {
        Storage::disk('s3')->put('full.mp3', 'fake content');

        $this->service->delete(location: 'full.mp3', backup: true);

        Storage::disk('s3')->assertMissing('full.mp3');
        Storage::disk('s3')->assertExists('backup/full.mp3.bak');
    }

    #[Test]
    public function getPresignedUrl(): void
    {
        $song = $this->service->storeUploadedFile($this->file, create_user());
        $url = $this->service->getPresignedUrl($song->storage_metadata->getPath());

        self::assertStringContainsString($song->storage_metadata->getPath(), $url);
    }
}
