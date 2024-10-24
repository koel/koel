<?php

namespace Tests\Integration\Models;

use App\Models\Song;
use App\Models\SongZipArchive;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class SongZipArchiveTest extends TestCase
{
    #[Test]
    public function addSongIntoArchive(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create(['path' => test_path('songs/full.mp3')]);

        $songZipArchive = new SongZipArchive();
        $songZipArchive->addSong($song);

        self::assertSame(1, $songZipArchive->getArchive()->numFiles);
        self::assertSame('full.mp3', $songZipArchive->getArchive()->getNameIndex(0));
    }

    #[Test]
    public function addMultipleSongsIntoArchive(): void
    {
        $songs = collect([
            Song::factory()->create(['path' => test_path('songs/full.mp3')]),
            Song::factory()->create(['path' => test_path('songs/lorem.mp3')]),
        ]);

        $songZipArchive = new SongZipArchive();
        $songZipArchive->addSongs($songs);

        self::assertSame(2, $songZipArchive->getArchive()->numFiles);
        self::assertSame('full.mp3', $songZipArchive->getArchive()->getNameIndex(0));
        self::assertSame('lorem.mp3', $songZipArchive->getArchive()->getNameIndex(1));
    }
}
