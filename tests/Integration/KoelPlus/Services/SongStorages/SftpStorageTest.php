<?php

namespace Tests\Integration\KoelPlus\Services\SongStorages;

use App\Helpers\Ulid;
use App\Services\SongStorages\SftpStorage;
use App\Values\UploadReference;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        Ulid::freeze('random');
        $user = create_user();
        $reference = $this->service->storeUploadedFile($this->file, $user);

        Storage::disk('sftp')->assertExists(Str::after($reference->location, 'sftp://'));

        self::assertSame("sftp://{$user->id}__random__song.mp3", $reference->location);
        self::assertSame(artifact_path('tmp/random/song.mp3'), $reference->localPath);
    }

    #[Test]
    public function undoUpload(): void
    {
        Storage::disk('sftp')->put('123__random__song.mp3', 'fake content');
        File::shouldReceive('delete')->once()->with('/tmp/random/song.mp3');

        $reference = UploadReference::make(
            location: 'sftp://123__random__song.mp3',
            localPath: '/tmp/random/song.mp3',
        );

        $this->service->undoUpload($reference);
    }

    #[Test]
    public function deleteSong(): void
    {
        $reference = $this->service->storeUploadedFile($this->file, create_user());
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
}
