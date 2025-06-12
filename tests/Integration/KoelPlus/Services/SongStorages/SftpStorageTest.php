<?php

namespace Tests\Integration\KoelPlus\Services\SongStorages;

use App\Models\Song;
use App\Services\SongStorages\SftpStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\test_path;

class SftpStorageTest extends PlusTestCase
{
    private SftpStorage $service;
    private UploadedFile $file;

    public function setUp(): void
    {
        parent::setUp();

        Storage::fake('sftp');
        $this->service = app(SftpStorage::class);
        $this->file = UploadedFile::fromFile(test_path('songs/full.mp3'), 'song.mp3'); //@phpstan-ignore-line
    }

    #[Test]
    public function storeUploadedFile(): void
    {
        self::assertEquals(0, Song::query()->where('storage', 'sftp')->count());

        $song = $this->service->storeUploadedFile($this->file, create_user());

        Storage::disk('sftp')->assertExists($song->storage_metadata->getPath());
        self::assertEquals(1, Song::query()->where('storage', 'sftp')->count());
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
    public function deleteSong(): void
    {
        $song = $this->service->storeUploadedFile($this->file, create_user());
        Storage::disk('sftp')->assertExists($song->storage_metadata->getPath());

        $this->service->delete($song->storage_metadata->getPath());
        Storage::disk('sftp')->assertMissing($song->storage_metadata->getPath());
    }

    #[Test]
    public function copyToLocal(): void
    {
        // Put a fake file on the fake SFTP disk
        Storage::disk('sftp')->put('music/test-song.mp3', 'fake mp3 content');

        $localPath = $this->service->copyToLocal('music/test-song.mp3');

        self::assertStringEqualsFile($localPath, 'fake mp3 content');
    }
}
