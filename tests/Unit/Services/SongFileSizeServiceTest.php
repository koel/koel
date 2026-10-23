<?php

namespace Tests\Unit\Services;

use App\Enums\FileSizeFillResult;
use App\Enums\SongStorageType;
use App\Models\Song;
use App\Services\SongFileSizeService;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class SongFileSizeServiceTest extends TestCase
{
    private SongFileSizeService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = new SongFileSizeService();
    }

    #[Test]
    public function fillFileSizeOfExistingFile(): void
    {
        $song = Song::factory()->createOne(['file_size' => null, 'path' => test_path('songs/full.mp3')]);

        self::assertSame(FileSizeFillResult::FILLED, $this->service->fillFileSize($song));
        self::assertSame(File::size(test_path('songs/full.mp3')), $song->refresh()->file_size);
    }

    #[Test]
    public function reportMissingFile(): void
    {
        $song = Song::factory()->createOne(['file_size' => null, 'path' => test_path('songs/missing.mp3')]);

        self::assertSame(FileSizeFillResult::MISSING, $this->service->fillFileSize($song));
        self::assertNull($song->refresh()->file_size);
    }

    #[Test]
    public function skipSongOnUnsupportedStorage(): void
    {
        $song = Song::factory()->createOne([
            'file_size' => null,
            'storage' => SongStorageType::S3,
            'path' => 's3://koel/song.mp3',
        ]);

        self::assertSame(FileSizeFillResult::SKIPPED, $this->service->fillFileSize($song));
        self::assertNull($song->refresh()->file_size);
    }
}
