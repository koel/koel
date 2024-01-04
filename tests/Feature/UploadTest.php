<?php

namespace Tests\Feature;

use App\Events\LibraryChanged;
use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Setting;
use App\Models\Song;
use App\Models\User;
use App\Services\UploadService;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;

class UploadTest extends TestCase
{
    private UploadService|MockInterface $uploadService;
    private UploadedFile $file;

    public function setUp(): void
    {
        parent::setUp();

        $this->uploadService = self::mock(UploadService::class);
        $this->file = UploadedFile::fromFile(__DIR__ . '/../songs/full.mp3', 'song.mp3');
    }

    public function testUnauthorizedPost(): void
    {
        Setting::set('media_path', '/media/koel');

        $this->uploadService
            ->shouldReceive('handleUploadedFile')
            ->never();

        $this->postAs('/api/upload', ['file' => $this->file])->assertForbidden();
    }

    /** @return array<mixed> */
    public function provideUploadExceptions(): array
    {
        return [
            [MediaPathNotSetException::class, Response::HTTP_FORBIDDEN],
            [SongUploadFailedException::class, Response::HTTP_BAD_REQUEST],
        ];
    }

    /** @dataProvider provideUploadExceptions */
    public function testPostShouldFail(string $exceptionClass, int $statusCode): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $this->uploadService
            ->shouldReceive('handleUploadedFile')
            ->once()
            ->with($this->file)
            ->andThrow($exceptionClass);

        $this->postAs('/api/upload', ['file' => $this->file], $admin)->assertStatus($statusCode);
    }

    public function testPost(): void
    {
        Event::fake(LibraryChanged::class);
        Setting::set('media_path', '/media/koel');

        /** @var Song $song */
        $song = Song::factory()->create();

        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $this->uploadService
            ->shouldReceive('handleUploadedFile')
            ->once()
            ->with($this->file)
            ->andReturn($song);

        $this->postAs('/api/upload', ['file' => $this->file], $admin)->assertJsonStructure(['song', 'album']);
        Event::assertDispatched(LibraryChanged::class);
    }
}
