<?php

namespace Tests\Integration\KoelPlus\Services;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Services\DownloadService;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class DownloadServiceTest extends PlusTestCase
{
    #[Test]
    public function serveARemoteDiskSongFromATemporaryCopyNamedAfterTheOriginalFile(): void
    {
        Storage::fake('sftp');
        Storage::disk('sftp')->put('01abc__01xyz__My Song.mp3', 'audio');

        $song = Song::factory()->createOne([
            'storage' => SongStorageType::SFTP,
            'path' => 'sftp://01abc__01xyz__My Song.mp3',
        ]);

        $downloadable = app(DownloadService::class)->getDownloadable($song);
        $response = $downloadable->toResponse();

        ob_start();
        $response->sendContent();
        ob_end_clean();

        self::assertSame('attachment; filename="My Song.mp3"', $response->headers->get('Content-Disposition'));
        self::assertFileDoesNotExist($downloadable->path);
    }
}
