<?php

namespace Tests\Feature\Commands;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Services\LibrarySummaryService;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class FillFileSizesCommandTest extends TestCase
{
    #[Test]
    public function fillMissingFileSizes(): void
    {
        $librarySummaryService = app(LibrarySummaryService::class);
        $librarySummaryService->markLibraryChanged();
        $libraryChangedAt = $librarySummaryService->getLibraryChangedAt();

        $songWithFile = Song::factory()->createOne(['file_size' => null, 'path' => test_path('songs/full.mp3')]);
        $songWithoutFile = Song::factory()->createOne(['file_size' => null, 'path' => test_path('songs/missing.mp3')]);
        $episode = Song::factory()->asEpisode()->createOne();
        $songOnS3 = Song::factory()->createOne([
            'file_size' => null,
            'storage' => SongStorageType::S3,
            'path' => 's3://koel/song.mp3',
        ]);

        $this
            ->artisan('koel:fill-file-sizes')
            ->expectsTable(['Result', 'Songs'], [
                ['Filled',                            1],
                ['File not found',                    1],
                ['Skipped (storage needs Koel Plus)', 1],
            ])
            ->assertSuccessful();

        self::assertSame(File::size(test_path('songs/full.mp3')), $songWithFile->refresh()->file_size);
        self::assertNull($songWithoutFile->refresh()->file_size);
        self::assertNull($episode->refresh()->file_size);
        self::assertNull($songOnS3->refresh()->file_size);
        self::assertNotSame($libraryChangedAt, $librarySummaryService->getLibraryChangedAt());
    }

    #[Test]
    public function doNothingWhenEverySongHasAFileSize(): void
    {
        Song::factory()->createOne();

        $this
            ->artisan('koel:fill-file-sizes')
            ->expectsOutput('Every song already has a file size.')
            ->assertSuccessful();
    }
}
