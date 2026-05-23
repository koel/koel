<?php

namespace Tests\Integration\KoelPlus\Services\SongStorages;

use App\Helpers\Ulid;
use App\Services\SongStorages\WebDAVStorage;
use App\Values\UploadReference;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\test_path;

class WebDAVStorageTest extends PlusTestCase
{
    private WebDAVStorage $service;
    private string $uploadedFilePath;

    public function setUp(): void
    {
        parent::setUp();

        Storage::fake('webdav');
        $this->service = app(WebDAVStorage::class);
        File::copy(test_path('songs/full.mp3'), artifact_path('tmp/random/song.mp3'));
        $this->uploadedFilePath = artifact_path('tmp/random/song.mp3');
    }

    #[Test]
    public function storeUploadedFile(): void
    {
        Ulid::freeze('random');
        $user = create_user();
        $reference = $this->service->storeUploadedFile($this->uploadedFilePath, $user);

        Storage::disk('webdav')->assertExists(Str::after($reference->location, 'webdav://'));

        self::assertSame("webdav://{$user->id}__random__song.mp3", $reference->location);
        self::assertSame(artifact_path('tmp/random/song.mp3'), $reference->localPath);
    }

    #[Test]
    public function undoUpload(): void
    {
        Storage::disk('webdav')->put('123__random__song.mp3', 'fake content');
        File::expects('delete')->with('/tmp/random/song.mp3');

        $reference = UploadReference::make(
            location: 'webdav://123__random__song.mp3',
            localPath: '/tmp/random/song.mp3',
        );

        $this->service->undoUpload($reference);
    }

    #[Test]
    public function deleteSong(): void
    {
        $reference = $this->service->storeUploadedFile($this->uploadedFilePath, create_user());
        $remotePath = Str::after($reference->location, 'webdav://');
        Storage::disk('webdav')->assertExists($remotePath);

        $this->service->delete($remotePath);

        Storage::disk('webdav')->assertMissing($remotePath);
    }

    #[Test]
    public function copyToLocal(): void
    {
        Storage::disk('webdav')->put('music/test-song.mp3', 'fake mp3 content');

        $localPath = $this->service->copyToLocal('music/test-song.mp3');

        self::assertStringEqualsFile($localPath, 'fake mp3 content');
    }
}
