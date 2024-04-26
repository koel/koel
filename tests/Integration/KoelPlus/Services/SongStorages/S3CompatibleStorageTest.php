<?php

namespace Tests\Integration\KoelPlus\Services\SongStorages;

use App\Models\Song;
use App\Services\SongStorages\S3CompatibleStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

        $this->service = app(S3CompatibleStorage::class);
        $this->file = UploadedFile::fromFile(test_path('songs/full.mp3'), 'song.mp3'); //@phpstan-ignore-line
    }

    public function testStoreUploadedFile(): void
    {
        self::assertEquals(0, Song::query()->where('storage', 's3')->count());

        Storage::fake('s3');
        $song = $this->service->storeUploadedFile($this->file, create_user());

        Storage::disk('s3')->assertExists($song->storage_metadata->getPath());
        self::assertEquals(1, Song::query()->where('storage', 's3')->count());
    }

    public function testStoringWithVisibilityPreference(): void
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

    public function testDelete(): void
    {
        Storage::fake('s3');

        $song = $this->service->storeUploadedFile($this->file, create_user());
        Storage::disk('s3')->assertExists($song->storage_metadata->getPath());

        $this->service->delete($song);
        Storage::disk('s3')->assertMissing($song->storage_metadata->getPath());
    }

    public function testGetPresignedUrl(): void
    {
        Storage::fake('s3');

        $song = $this->service->storeUploadedFile($this->file, create_user());
        $url = $this->service->getSongPresignedUrl($song);

        self::assertStringContainsString($song->storage_metadata->getPath(), $url);
    }
}
