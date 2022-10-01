<?php

namespace Tests\Integration\Models;

use App\Models\Song;
use App\Models\SongZipArchive;
use Tests\TestCase;

class SongZipArchiveTest extends TestCase
{
    public function testAddSongIntoArchive(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create(['path' => realpath(__DIR__ . '/../../songs/full.mp3')]);

        $songZipArchive = new SongZipArchive();
        $songZipArchive->addSong($song);

        self::assertSame(1, $songZipArchive->getArchive()->numFiles);
        self::assertSame('full.mp3', $songZipArchive->getArchive()->getNameIndex(0));
    }

    public function testAddMultipleSongsIntoArchive(): void
    {
        $songs = collect([
            Song::factory()->create(['path' => realpath(__DIR__ . '/../../songs/full.mp3')]),
            Song::factory()->create(['path' => realpath(__DIR__ . '/../../songs/lorem.mp3')]),
        ]);

        $songZipArchive = new SongZipArchive();
        $songZipArchive->addSongs($songs);

        self::assertSame(2, $songZipArchive->getArchive()->numFiles);
        self::assertSame('full.mp3', $songZipArchive->getArchive()->getNameIndex(0));
        self::assertSame('lorem.mp3', $songZipArchive->getArchive()->getNameIndex(1));
    }
}
