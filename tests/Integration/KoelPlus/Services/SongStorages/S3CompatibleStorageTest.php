<?php

namespace Tests\Integration\KoelPlus\Services\SongStorages;

use App\Helpers\Ulid;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Values\UploadReference;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        Ulid::freeze('random');
        $user = create_user();
        $reference = $this->service->storeUploadedFile($this->file, $user);

        Storage::disk('s3')->assertExists(Str::after($reference->location, 's3://koel/')); // 'koel' is the bucket name

        self::assertSame("s3://koel/{$user->id}__random__song.mp3", $reference->location);
        self::assertSame(artifact_path('tmp/random/song.mp3'), $reference->localPath);
    }

    #[Test]
    public function undoUpload(): void
    {
        Storage::disk('s3')->put('123__random__song.mp3', 'fake content');
        File::shouldReceive('delete')->once()->with('/tmp/random/song.mp3');

        $reference = UploadReference::make(
            location: 's3://koel/123__random__song.mp3', // 'koel' is the bucket name
            localPath: '/tmp/random/song.mp3',
        );

        $this->service->undoUpload($reference);
        Storage::disk('s3')->assertMissing('123__random__song.mp3');
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
        $reference = $this->service->storeUploadedFile($this->file, create_user());
        $url = $this->service->getPresignedUrl(Str::after($reference->location, 's3://koel/'));

        self::assertStringContainsString(Str::after($reference->location, 's3://koel/'), $url);
    }
}
