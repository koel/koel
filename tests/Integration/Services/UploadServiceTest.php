<?php

namespace Tests\Integration\Services;

use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Setting;
use App\Services\UploadService;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class UploadServiceTest extends TestCase
{
    private UploadService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(UploadService::class);
    }

    public function testHandleUploadedFileWithMediaPathNotSet(): void
    {
        Setting::set('media_path');

        self::expectException(MediaPathNotSetException::class);
        $this->service->handleUploadedFile(Mockery::mock(UploadedFile::class), create_user());
    }

    public function testHandleUploadedFileFails(): void
    {
        Setting::set('media_path', public_path('sandbox/media'));

        self::expectException(SongUploadFailedException::class);
        $this->service->handleUploadedFile(UploadedFile::fake()->create('fake.mp3'), create_user());
    }

    public function testHandleUploadedFile(): void
    {
        Setting::set('media_path', public_path('sandbox/media'));
        $user = create_user();

        $song = $this->service->handleUploadedFile(UploadedFile::fromFile(test_path('songs/full.mp3')), $user); //@phpstan-ignore-line

        self::assertSame($song->owner_id, $user->id);
        self::assertSame(public_path("sandbox/media/__KOEL_UPLOADS_\${$user->id}__/full.mp3"), $song->path);
    }

    public function testUploadingTakesIntoAccountUploadVisibilityPreference(): void
    {
        $user = create_user();
        $user->preferences->makeUploadsPublic = true;
        $user->save();

        Setting::set('media_path', public_path('sandbox/media'));
        $song = $this->service->handleUploadedFile(UploadedFile::fromFile(test_path('songs/full.mp3')), $user); //@phpstan-ignore-line
        self::assertTrue($song->is_public);

        $user->preferences->makeUploadsPublic = false;
        $user->save();
        $privateSongs = $this->service->handleUploadedFile(UploadedFile::fromFile(test_path('songs/full.mp3')), $user); //@phpstan-ignore-line
        self::assertFalse($privateSongs->is_public);
    }
}
