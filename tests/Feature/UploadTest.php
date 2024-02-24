<?php

namespace Tests\Feature;

use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Setting;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\test_path;

class UploadTest extends TestCase
{
    private UploadedFile $file;

    public function setUp(): void
    {
        parent::setUp();

        $this->file = UploadedFile::fromFile(test_path('songs/full.mp3'), 'song.mp3'); //@phpstan-ignore-line
    }

    public function testUnauthorizedPost(): void
    {
        Setting::set('media_path', '');

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
        Setting::set('media_path', '');

        $this->postAs('/api/upload', ['file' => $this->file], create_admin())->assertForbidden();
    }

    public function testUploadSuccessful(): void
    {
        Setting::set('media_path', public_path('sandbox/media'));

        $this->postAs('/api/upload', ['file' => $this->file], create_admin())->assertJsonStructure(['song', 'album']);
    }
}
