<?php

namespace Tests\Feature;

use App\Events\MediaCacheObsolete;
use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Setting;
use App\Models\Song;
use App\Models\User;
use App\Services\UploadService;
use Illuminate\Http\UploadedFile;

class UploadTest extends TestCase
{
    private $uploadService;

    public function setUp(): void
    {
        parent::setUp();

        $this->uploadService = self::mock(UploadService::class);
    }

    public function testUnauthorizedPost(): void
    {
        Setting::set('media_path', '/media/koel');
        $this->doesntExpectEvents(MediaCacheObsolete::class);
        $file = UploadedFile::fake()->create('foo.mp3', 2048);

        $this->uploadService
            ->shouldReceive('handleUploadedFile')
            ->never();

        $this->postAsUser(
            '/api/upload',
            ['file' => $file],
            User::factory()->create()
        )->assertStatus(403);
    }

    /** @return array<mixed> */
    public function provideUploadExceptions(): array
    {
        return [
            [MediaPathNotSetException::class, 403],
            [SongUploadFailedException::class, 400],
        ];
    }

    /** @dataProvider provideUploadExceptions */
    public function testPostShouldFail(string $exceptionClass, int $statusCode): void
    {
        $this->doesntExpectEvents(MediaCacheObsolete::class);
        $file = UploadedFile::fake()->create('foo.mp3', 2048);

        $this->uploadService
            ->shouldReceive('handleUploadedFile')
            ->once()
            ->with($file)
            ->andThrow($exceptionClass);

        $this->postAsUser(
            '/api/upload',
            ['file' => $file],
            User::factory()->admin()->create()
        )->assertStatus($statusCode);
    }

    public function testPost(): void
    {
        Setting::set('media_path', '/media/koel');
        $this->expectsEvents(MediaCacheObsolete::class);
        $file = UploadedFile::fake()->create('foo.mp3', 2048);
        /** @var Song $song */
        $song = Song::factory()->create();
        $this->uploadService
            ->shouldReceive('handleUploadedFile')
            ->once()
            ->with($file)
            ->andReturn($song);

        $this->postAsUser(
            '/api/upload',
            ['file' => $file],
            User::factory()->admin()->create()
        )->assertJsonStructure([
            'album',
            'artist',
        ]);
    }
}
