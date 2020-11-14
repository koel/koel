<?php

namespace Tests\Integration\Models;

use App\Models\Song;
use App\Models\SongZipArchive;
use Tests\TestCase;

class SongZipArchiveTest extends TestCase
{
    public function testAddSongIntoArchive(): void
    {
        $song = Song::factory()->create(['path' => realpath(__DIR__.'/../../songs/full.mp3')]);

        $songZipArchive = new SongZipArchive();
        $songZipArchive->addSong($song);

        self::assertEquals(1, $songZipArchive->getArchive()->numFiles);
        self::assertEquals('full.mp3', $songZipArchive->getArchive()->getNameIndex(0));
    }

    public function testAddMultipleSongsIntoArchive(): void
    {
        $songs = collect([
            Song::factory()->create(['path' => realpath(__DIR__.'/../../songs/full.mp3')]),
            Song::factory()->create(['path' => realpath(__DIR__.'/../../songs/lorem.mp3')]),
        ]);

        $songZipArchive = new SongZipArchive();
        $songZipArchive->addSongs($songs);

        self::assertEquals(2, $songZipArchive->getArchive()->numFiles);
        self::assertEquals('full.mp3', $songZipArchive->getArchive()->getNameIndex(0));
        self::assertEquals('lorem.mp3', $songZipArchive->getArchive()->getNameIndex(1));
    }
}
