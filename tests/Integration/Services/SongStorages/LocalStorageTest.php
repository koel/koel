<?php

namespace Tests\Integration\Services\SongStorages;

use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Setting;
use App\Services\SongStorages\LocalStorage;
use Illuminate\Http\UploadedFile;
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
    public function storeUploadedFileFails(): void
    {
        Setting::set('media_path', public_path('sandbox/media'));

        $this->expectException(SongUploadFailedException::class);
        $this->service->storeUploadedFile(UploadedFile::fake()->create('fake.mp3'), create_user());
    }

    #[Test]
    public function storeUploadedFile(): void
    {
        Setting::set('media_path', public_path('sandbox/media'));
        $user = create_user();

        $song = $this->service->storeUploadedFile(UploadedFile::fromFile(test_path('songs/full.mp3')), $user); //@phpstan-ignore-line

        self::assertSame($song->owner_id, $user->id);
        self::assertSame(public_path("sandbox/media/__KOEL_UPLOADS_\${$user->id}__/full.mp3"), $song->path);
    }

    #[Test]
    public function storingWithVisibilityPreference(): void
    {
        $user = create_user();
        $user->preferences->makeUploadsPublic = true;
        $user->save();

        Setting::set('media_path', public_path('sandbox/media'));
        $song = $this->service->storeUploadedFile(UploadedFile::fromFile(test_path('songs/full.mp3')), $user); //@phpstan-ignore-line
        self::assertTrue($song->is_public);

        $user->preferences->makeUploadsPublic = false;
        $user->save();
        $privateSongs = $this->service->storeUploadedFile(UploadedFile::fromFile(test_path('songs/full.mp3')), $user); //@phpstan-ignore-line
        self::assertFalse($privateSongs->is_public);
    }
}
