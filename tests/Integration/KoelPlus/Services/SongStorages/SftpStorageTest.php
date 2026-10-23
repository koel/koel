<?php

namespace Tests\Integration\KoelPlus\Services\SongStorages;

use App\Helpers\Ulid;
use App\Services\SongStorages\SftpStorage;
use App\Values\UploadReference;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\test_path;

class SftpStorageTest extends PlusTestCase
{
    private SftpStorage $service;
    private string $uploadedFilePath;

    public function setUp(): void
    {
        parent::setUp();

        Storage::fake('sftp');
        $this->service = app(SftpStorage::class);
        File::copy(test_path('songs/full.mp3'), artifact_path('tmp/random/song.mp3'));
        $this->uploadedFilePath = artifact_path('tmp/random/song.mp3');
    }

    #[Test]
    public function storeUploadedFile(): void
    {
        Ulid::freeze('random');
        $user = create_user();
        $reference = $this->service->storeUploadedFile($this->uploadedFilePath, $user);

        Storage::disk('sftp')->assertExists(Str::after($reference->location, 'sftp://'));

        self::assertSame("sftp://{$user->public_id}__random__song.mp3", $reference->location);
        self::assertSame(artifact_path('tmp/random/song.mp3'), $reference->localPath);
    }

    #[Test]
    public function undoUpload(): void
    {
        Storage::disk('sftp')->put('123__random__song.mp3', 'fake content');
        File::expects('delete')->with('/tmp/random/song.mp3');

        $reference = UploadReference::make(
            location: 'sftp://123__random__song.mp3',
            localPath: '/tmp/random/song.mp3',
            originalFileName: 'song.mp3',
        );

        $this->service->undoUpload($reference);
    }

    #[Test]
    public function deleteSong(): void
    {
        $reference = $this->service->storeUploadedFile($this->uploadedFilePath, create_user());
        $remotePath = Str::after($reference->location, 'sftp://');
        Storage::disk('sftp')->assertExists($remotePath);

        $this->service->delete($remotePath);

        Storage::disk('sftp')->assertMissing($remotePath);
    }

    #[Test]
    public function copyToLocal(): void
    {
        // Put a fake file on the fake SFTP disk
        Storage::disk('sftp')->put('music/test-song.mp3', 'fake mp3 content');

        $localPath = $this->service->copyToLocal('music/test-song.mp3');

        self::assertStringEqualsFile($localPath, 'fake mp3 content');
    }

    #[Test]
    public function downloadARemoteFileOnceForRepeatedStreaming(): void
    {
        Storage::disk('sftp')->put('music/reused-song.mp3', 'fake mp3 content');

        $firstCopy = $this->service->getReusableLocalCopy('music/reused-song.mp3');
        touch($firstCopy, now()->subDays(2)->getTimestamp());
        Storage::disk('sftp')->put('music/reused-song.mp3', 'changed content');
        $secondCopy = $this->service->getReusableLocalCopy('music/reused-song.mp3');

        self::assertSame($firstCopy, $secondCopy);
        self::assertStringEqualsFile($secondCopy, 'fake mp3 content');
        clearstatcache();
        self::assertGreaterThan(now()->subHour()->getTimestamp(), filemtime($secondCopy));

        File::delete($secondCopy);
    }

    #[Test]
    public function waitForAnotherRequestDownloadingTheSameFile(): void
    {
        $this->freezeTime();
        Sleep::fake(syncWithCarbon: true);

        Storage::disk('sftp')->put('music/locked-song.mp3', 'fake mp3 content');
        Cache::lock(cache_key('reusable local copy', 'sftp', 'music/locked-song.mp3'), 600)->get();

        $this->expectException(LockTimeoutException::class);

        $this->service->getReusableLocalCopy('music/locked-song.mp3');
    }

    #[Test]
    public function getFileSize(): void
    {
        Storage::disk('sftp')->put('song.mp3', 'fake content');

        self::assertSame(12, $this->service->getFileSize('song.mp3'));
    }

    #[Test]
    public function getFileSizeOfMissingFile(): void
    {
        self::assertNull($this->service->getFileSize('missing.mp3'));
    }
}
