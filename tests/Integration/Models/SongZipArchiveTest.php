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
        // Given a song
        $song = factory(Song::class)->create([
            'path' => realpath(__DIR__.'/../../songs/full.mp3'),
        ]);

        // When I add the song into the archive
        $songArchive = new SongZipArchive();
        $songArchive->addSong($song);

        // Then I see the archive contains one file
        $archive = $songArchive->getArchive();
        $this->assertEquals(1, $archive->numFiles);

        // and the file is our song
        $this->assertEquals('full.mp3', $archive->getNameIndex(0));
    }

    /** @test */
    public function multiple_songs_can_be_added_into_an_archive()
    {
        // Given some songs
        $songs = collect([
            factory(Song::class)->create([
                'path' => realpath(__DIR__.'/../../songs/full.mp3'),
            ]),
            factory(Song::class)->create([
                'path' => realpath(__DIR__.'/../../songs/lorem.mp3'),
            ]),
        ]);

        // When I add the songs into the archive
        $songArchive = new SongZipArchive();
        $songArchive->addSongs($songs);

        // Then I see the archive contains two files
        $archive = $songArchive->getArchive();
        $this->assertEquals(2, $archive->numFiles);

        // and the files are our songs
        $this->assertEquals('full.mp3', $archive->getNameIndex(0));
        $this->assertEquals('lorem.mp3', $archive->getNameIndex(1));
    }
}
