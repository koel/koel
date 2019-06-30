<?php

namespace Tests\Integration\Models;

use App\Models\Song;
use App\Models\SongZipArchive;
use Tests\TestCase;

class SongZipArchiveTest extends TestCase
{
    /** @test */
    public function a_song_can_be_added_into_an_archive()
    {
        $song = factory(Song::class)->create([
            'path' => realpath(__DIR__.'/../../songs/full.mp3'),
        ]);

        $archive = new SongZipArchive();
        $archive->addSong($song);

        $this->assertEquals(1, $archive->numFiles);
        $this->assertEquals('full.mp3', $archive->getNameIndex(0));
    }

    /** @test */
    public function multiple_songs_can_be_added_into_an_archive()
    {
        $songs = collect([
            factory(Song::class)->create([
                'path' => realpath(__DIR__.'/../../songs/full.mp3'),
            ]),
            factory(Song::class)->create([
                'path' => realpath(__DIR__.'/../../songs/lorem.mp3'),
            ]),
        ]);

        $archive = new SongZipArchive();
        $archive->addSongs($songs);

        $this->assertEquals(2, $archive->numFiles);
        $this->assertEquals('full.mp3', $archive->getNameIndex(0));
        $this->assertEquals('lorem.mp3', $archive->getNameIndex(1));
    }
}
