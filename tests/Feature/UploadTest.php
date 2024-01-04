<?php

namespace Tests\Feature;

use App\Events\LibraryChanged;
use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;

class UploadTest extends TestCase
{
    private UploadedFile $file;

    public function setUp(): void
    {
        parent::setUp();

        $this->file = UploadedFile::fromFile(__DIR__ . '/../songs/full.mp3', 'song.mp3');
    }

    public function testUnauthorizedPost(): void
    {
        Setting::set('media_path');

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

    public function testUploadFailsIfMediaPathIsNotSet(): void
    {
        Setting::set('media_path');

        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $this->postAs('/api/upload', ['file' => $this->file], $admin)->assertForbidden();
    }

    public function testUploadSuccessful(): void
    {
        Event::fake(LibraryChanged::class);
        Setting::set('media_path', public_path('sandbox/media'));

        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $this->postAs('/api/upload', ['file' => $this->file], $admin)->assertJsonStructure(['song', 'album']);
        Event::assertDispatched(LibraryChanged::class);
    }
}
