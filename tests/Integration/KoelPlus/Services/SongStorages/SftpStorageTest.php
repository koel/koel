<?php

namespace Tests\Integration\KoelPlus\Services\SongStorages;

use App\Models\Song;
use App\Services\SongStorages\SftpStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

        $this->service = app(SftpStorage::class);
        $this->file = UploadedFile::fromFile(test_path('songs/full.mp3'), 'song.mp3'); //@phpstan-ignore-line
    }

    public function testStoreUploadedFile(): void
    {
        self::assertEquals(0, Song::query()->where('storage', 'sftp')->count());

        Storage::fake('sftp');
        $song = $this->service->storeUploadedFile($this->file, create_user());

        Storage::disk('sftp')->assertExists($song->storage_metadata->getPath());
        self::assertEquals(1, Song::query()->where('storage', 'sftp')->count());
    }

    public function testStoringWithVisibilityPreference(): void
    {
        Storage::fake('sftp');

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
        Storage::fake('sftp');

        $song = $this->service->storeUploadedFile($this->file, create_user());
        Storage::disk('sftp')->assertExists($song->storage_metadata->getPath());

        $this->service->delete($song);
        Storage::disk('sftp')->assertMissing($song->storage_metadata->getPath());
    }

    public function testGetSongContent(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create();

        Storage::fake('sftp');
        Storage::shouldReceive('disk->get')->with($song->storage_metadata->getPath())->andReturn('binary-content');

        self::assertEquals('binary-content', $this->service->getSongContent($song));
    }

    public function testCopyToLocal(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create();

        Storage::fake('sftp');
        Storage::shouldReceive('disk->get')->with($song->storage_metadata->getPath())->andReturn('binary-content');

        $localPath = $this->service->copyToLocal($song);

        self::assertStringEqualsFile($localPath, 'binary-content');
    }
}
