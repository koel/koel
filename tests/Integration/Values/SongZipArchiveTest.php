<?php

namespace Tests\Integration\Values;

use App\Models\Song;
use App\Values\SongZipArchive;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class SongZipArchiveTest extends TestCase
{
    #[Test]
    public function addSongIntoArchive(): void
    {
        $song = Song::factory()->createOne(['path' => test_path('songs/full.mp3')]);

        $songZipArchive = new SongZipArchive();
        $songZipArchive->addSong($song);

        self::assertSame(1, $songZipArchive->getArchive()->numFiles);
        self::assertSame('full.mp3', $songZipArchive->getArchive()->getNameIndex(0));
    }

    #[Test]
    public function addMultipleSongsIntoArchive(): void
    {
        $songs = collect([
            Song::factory()->createOne(['path' => test_path('songs/full.mp3')]),
            Song::factory()->createOne(['path' => test_path('songs/lorem.mp3')]),
        ]);

        $songZipArchive = new SongZipArchive();
        $songZipArchive->addSongs($songs);

        self::assertSame(2, $songZipArchive->getArchive()->numFiles);
        self::assertSame('full.mp3', $songZipArchive->getArchive()->getNameIndex(0));
        self::assertSame('lorem.mp3', $songZipArchive->getArchive()->getNameIndex(1));
    }
}
