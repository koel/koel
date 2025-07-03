<?php

namespace Tests\Integration\Services\SongStorages;

use App\Exceptions\MediaPathNotSetException;
use App\Models\Setting;
use App\Services\SongStorages\LocalStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class LocalStorageTest extends TestCase
{
    private LocalStorage $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(LocalStorage::class);
    }

    #[Test]
    public function storeUploadedFileWithMediaPathNotSet(): void
    {
        Setting::set('media_path', '');

        $this->expectException(MediaPathNotSetException::class);
        $this->service->storeUploadedFile(Mockery::mock(UploadedFile::class), create_user());
    }

    #[Test]
    public function storeUploadedFile(): void
    {
        Setting::set('media_path', public_path('sandbox/media'));
        File::copy(test_path('songs/full.mp3'), artifact_path('tmp/random/full.mp3'));

        $user = create_user();

        $reference = $this->service->storeUploadedFile(artifact_path('tmp/random/full.mp3'), $user);

        self::assertSame(public_path("sandbox/media/__KOEL_UPLOADS_\${$user->id}__/full.mp3"), $reference->location);
        self::assertSame($reference->location, $reference->localPath);
    }
}
