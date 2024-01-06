<?php

namespace Tests\Integration\Services;

use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Setting;
use App\Models\User;
use App\Services\UploadService;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

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

        /** @var User $user */
        $user = User::factory()->create();

        self::expectException(MediaPathNotSetException::class);
        $this->service->handleUploadedFile(Mockery::mock(UploadedFile::class), $user);
    }

    public function testHandleUploadedFileFails(): void
    {
        Setting::set('media_path', public_path('sandbox/media'));

        /** @var User $user */
        $user = User::factory()->create();

        self::expectException(SongUploadFailedException::class);
        $this->service->handleUploadedFile(UploadedFile::fake()->create('fake.mp3'), $user);
    }

    public function testHandleUploadedFile(): void
    {
        Setting::set('media_path', public_path('sandbox/media'));

        /** @var User $user */
        $user = User::factory()->create();

        $song = $this->service->handleUploadedFile(UploadedFile::fromFile(test_path('songs/full.mp3')), $user);

        self::assertSame($song->owner_id, $user->id);
        self::assertSame(public_path("sandbox/media/__KOEL_UPLOADS_\${$user->id}__/full.mp3"), $song->path);
    }
}
