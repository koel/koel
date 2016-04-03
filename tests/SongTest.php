<?php

use App\Events\LibraryChanged;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class SongTest extends TestCase
{
    use DatabaseTransactions, WithoutMiddleware;

    public function testSingleUpdateAllInfo()
    {
        $this->expectsEvents(LibraryChanged::class);

        $this->createSampleMediaSet();
        $song = Song::orderBy('id', 'desc')->first();

        $this->actingAs(factory(User::class, 'admin')->create())
            ->put('/api/songs', [
                'songs' => [$song->id],
                'data' => [
                    'title' => 'Foo Bar',
                    'artistName' => 'John Cena',
                    'albumName' => 'One by One',
                    'lyrics' => 'Lorem ipsum dolor sic amet.',
                    'track' => 1,
                ],
            ])
            ->seeStatusCode(200);

        $artist = Artist::whereName('John Cena')->first();
        $this->assertNotNull($artist);

        $album = Album::whereName('One by One')->first();
        $this->assertNotNull($album);

        $this->seeInDatabase('songs', [
            'id' => $song->id,
            'album_id' => $album->id,
            'lyrics' => 'Lorem ipsum dolor sic amet.',
            'track' => 1,
        ]);
    }

    public function testSingleUpdateSomeInfo()
    {
        $this->createSampleMediaSet();
        $song = Song::orderBy('id', 'desc')->first();
        $originalArtistId = $song->album->artist->id;

        $this->actingAs(factory(User::class, 'admin')->create())
            ->put('/api/songs', [
                'songs' => [$song->id],
                'data' => [
                    'title' => '',
                    'artistName' => '',
                    'albumName' => 'One by One',
                    'lyrics' => 'Lorem ipsum dolor sic amet.',
                    'track' => 1,
                ],
            ])
            ->seeStatusCode(200);

        // We don't expect the song's artist to change
        $this->assertEquals($originalArtistId, Song::find($song->id)->album->artist->id);

        // But we expect a new album to be created for this artist and contain this song
        $this->assertEquals('One by One', Song::find($song->id)->album->name);
    }

    public function testMultipleUpdateAllInfo()
    {
        $this->createSampleMediaSet();
        $songIds = Song::orderBy('id', 'desc')->take(3)->pluck('id')->toArray();

        $this->actingAs(factory(User::class, 'admin')->create())
            ->put('/api/songs', [
                'songs' => $songIds,
                'data' => [
                    'title' => 'Foo Bar',
                    'artistName' => 'John Cena',
                    'albumName' => 'One by One',
                    'lyrics' => 'Lorem ipsum dolor sic amet.',
                    'track' => 1,
                ],
            ])
            ->seeStatusCode(200);

        $songs = Song::orderBy('id', 'desc')->take(3)->get();

        // Even though we post the title and lyrics, we don't expect them to take any effect
        $this->assertNotEquals('Foo Bar', $songs[0]->title);
        $this->assertNotEquals('Lorem ipsum dolor sic amet.', $songs[2]->lyrics);
        $this->assertNotEquals(1, $songs[2]->track);

        // But all of these songs must now belong to a new album and artist set
        $this->assertEquals('One by One', $songs[0]->album->name);
        $this->assertEquals('One by One', $songs[1]->album->name);
        $this->assertEquals('One by One', $songs[2]->album->name);

        $this->assertEquals('John Cena', $songs[0]->album->artist->name);
        $this->assertEquals('John Cena', $songs[1]->album->artist->name);
        $this->assertEquals('John Cena', $songs[2]->album->artist->name);
    }

    public function testMultipleUpdateSomeInfo()
    {
        $this->createSampleMediaSet();
        $originalSongs = Song::orderBy('id', 'desc')->take(3)->get();
        $songIds = $originalSongs->pluck('id')->toArray();

        $this->actingAs(factory(User::class, 'admin')->create())
            ->put('/api/songs', [
                'songs' => $songIds,
                'data' => [
                    'title' => 'Foo Bar',
                    'artistName' => 'John Cena',
                    'albumName' => '',
                    'lyrics' => 'Lorem ipsum dolor sic amet.',
                    'track' => 1,
                ],
            ])
            ->seeStatusCode(200);

        $songs = Song::orderBy('id', 'desc')->take(3)->get();

        // Even though the album name doesn't change, a new artist should have been created
        // and thus, a new album with the same name was created as well.
        $this->assertEquals($songs[0]->album->name, $originalSongs[0]->album->name);
        $this->assertNotEquals($songs[0]->album->id, $originalSongs[0]->album->id);
        $this->assertEquals($songs[1]->album->name, $originalSongs[1]->album->name);
        $this->assertNotEquals($songs[1]->album->id, $originalSongs[1]->album->id);
        $this->assertEquals($songs[2]->album->name, $originalSongs[2]->album->name);
        $this->assertNotEquals($songs[2]->album->id, $originalSongs[2]->album->id);

        // And of course, the new artist is...
        $this->assertEquals('John Cena', $songs[0]->album->artist->name); // JOHN CENA!!!
        $this->assertEquals('John Cena', $songs[1]->album->artist->name); // JOHN CENA!!!
        $this->assertEquals('John Cena', $songs[2]->album->artist->name); // And... JOHN CENAAAAAAAAAAA!!!
    }
}
