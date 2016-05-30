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

    public function setUp()
    {
        parent::setUp();
        $this->createSampleMediaSet();
    }

    public function testSingleUpdateAllInfoNoCompilation()
    {
        $this->expectsEvents(LibraryChanged::class);
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
                    'compilationState' => 0,
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

    public function testSingleUpdateSomeInfoNoCompilation()
    {
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
                    'compilationState' => 0,
                ],
            ])
            ->seeStatusCode(200);

        // We don't expect the song's artist to change
        $this->assertEquals($originalArtistId, Song::find($song->id)->album->artist->id);

        // But we expect a new album to be created for this artist and contain this song
        $this->assertEquals('One by One', Song::find($song->id)->album->name);
    }

    public function testMultipleUpdateAllInfoNoCompilation()
    {
        $songIds = Song::orderBy('id', 'desc')->take(3)->pluck('id')->toArray();

        $this->actingAs(factory(User::class, 'admin')->create())
            ->put('/api/songs', [
                'songs' => $songIds,
                'data' => [
                    'title' => 'foo',
                    'artistName' => 'John Cena',
                    'albumName' => 'One by One',
                    'lyrics' => 'bar',
                    'track' => 9999,
                    'compilationState' => 0,
                ],
            ])
            ->seeStatusCode(200);

        $songs = Song::orderBy('id', 'desc')->take(3)->get();

        // Even though we post the title, lyrics, and tracks, we don't expect them to take any effect
        // because we're updating multiple songs here.
        $this->assertNotEquals('foo', $songs[0]->title);
        $this->assertNotEquals('bar', $songs[2]->lyrics);
        $this->assertNotEquals(9999, $songs[2]->track);

        // But all of these songs must now belong to a new album and artist set
        $this->assertEquals('One by One', $songs[0]->album->name);
        $this->assertEquals('One by One', $songs[1]->album->name);
        $this->assertEquals('One by One', $songs[2]->album->name);

        $this->assertEquals('John Cena', $songs[0]->album->artist->name);
        $this->assertEquals('John Cena', $songs[1]->album->artist->name);
        $this->assertEquals('John Cena', $songs[2]->album->artist->name);
    }

    public function testMultipleUpdateSomeInfoNoCompilation()
    {
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
                    'compilationState' => 0,
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

    public function testSingleUpdateAllInfoYesCompilation()
    {
        $admin = factory(User::class, 'admin')->create();
        $song = Song::orderBy('id', 'desc')->first();

        $this->actingAs($admin)
            ->put('/api/songs', [
                'songs' => [$song->id],
                'data' => [
                    'title' => 'Foo Bar',
                    'artistName' => 'John Cena',
                    'albumName' => 'One by One',
                    'lyrics' => 'Lorem ipsum dolor sic amet.',
                    'track' => 1,
                    'compilationState' => 1,
                ],
            ])
            ->seeStatusCode(200);

        $compilationAlbum = Album::whereArtistIdAndName(Artist::VARIOUS_ID, 'One by One')->first();
        $this->assertNotNull($compilationAlbum);

        $contributingArtist = Artist::whereName('John Cena')->first();
        $this->assertNotNull($contributingArtist);

        $this->seeInDatabase('songs', [
            'id' => $song->id,
            'contributing_artist_id' => $contributingArtist->id,
            'album_id' => $compilationAlbum->id,
            'lyrics' => 'Lorem ipsum dolor sic amet.',
            'track' => 1,
        ]);

        // Now try changing stuff and make sure things work.
        // Case 1: Keep compilation state and artist the same
        $this->actingAs($admin)
            ->put('/api/songs', [
                'songs' => [$song->id],
                'data' => [
                    'title' => 'Barz Qux',
                    'artistName' => 'John Cena',
                    'albumName' => 'Two by Two',
                    'lyrics' => 'Lorem ipsum dolor sic amet.',
                    'track' => 1,
                    'compilationState' => 2,
                ],
            ])
            ->seeStatusCode(200);

        $compilationAlbum = Album::whereArtistIdAndName(Artist::VARIOUS_ID, 'Two by Two')->first();
        $this->assertNotNull($compilationAlbum);

        $contributingArtist = Artist::whereName('John Cena')->first();
        $this->assertNotNull($contributingArtist);

        $this->seeInDatabase('songs', [
            'id' => $song->id,
            'contributing_artist_id' => $contributingArtist->id,
            'album_id' => $compilationAlbum->id,
        ]);

        // Case 2: Keep compilation state, but change the artist.
        $this->actingAs($admin)
            ->put('/api/songs', [
                'songs' => [$song->id],
                'data' => [
                    'title' => 'Barz Qux',
                    'artistName' => 'Foo Fighters',
                    'albumName' => 'One by One',
                    'lyrics' => 'Lorem ipsum dolor sic amet.',
                    'track' => 1,
                    'compilationState' => 2,
                ],
            ])
            ->seeStatusCode(200);

        $compilationAlbum = Album::whereArtistIdAndName(Artist::VARIOUS_ID, 'One by One')->first();
        $this->assertNotNull($compilationAlbum);

        $contributingArtist = Artist::whereName('Foo Fighters')->first();
        $this->assertNotNull($contributingArtist);

        $this->seeInDatabase('songs', [
            'id' => $song->id,
            'contributing_artist_id' => $contributingArtist->id,
            'album_id' => $compilationAlbum->id,
        ]);

        // Case 3: Change compilation state only
        $this->actingAs($admin)
            ->put('/api/songs', [
                'songs' => [$song->id],
                'data' => [
                    'title' => 'Barz Qux',
                    'artistName' => 'Foo Fighters',
                    'albumName' => 'One by One',
                    'lyrics' => 'Lorem ipsum dolor sic amet.',
                    'track' => 1,
                    'compilationState' => 0,
                ],
            ])
            ->seeStatusCode(200);

        $artist = Artist::whereName('Foo Fighters')->first();
        $this->assertNotNull($artist);
        $album = Album::whereArtistIdAndName($artist->id, 'One by One')->first();

        $this->seeInDatabase('songs', [
            'id' => $song->id,
            'contributing_artist_id' => null,
            'album_id' => $album->id,
        ]);

        // Case 3: Change compilation state and artist
        // Remember to set the compliation state back to 1
        $this->actingAs($admin)
            ->put('/api/songs', [
                'songs' => [$song->id],
                'data' => [
                    'title' => 'Barz Qux',
                    'artistName' => 'Foo Fighters',
                    'albumName' => 'One by One',
                    'lyrics' => 'Lorem ipsum dolor sic amet.',
                    'track' => 1,
                    'compilationState' => 1,
                ],
            ])
            ->put('/api/songs', [
                'songs' => [$song->id],
                'data' => [
                    'title' => 'Twilight of the Thunder God',
                    'artistName' => 'Amon Amarth',
                    'albumName' => 'Twilight of the Thunder God',
                    'lyrics' => 'Thor! Nanananananana Batman.',
                    'track' => 1,
                    'compilationState' => 0,
                ],
            ])
            ->seeStatusCode(200);

        $artist = Artist::whereName('Amon Amarth')->first();
        $this->assertNotNull($artist);
        $album = Album::whereArtistIdAndName($artist->id, 'Twilight of the Thunder God')->first();
        $this->assertNotNull($album);
        $this->seeInDatabase('songs', [
            'id' => $song->id,
            'contributing_artist_id' => null,
            'album_id' => $album->id,
            'lyrics' => 'Thor! Nanananananana Batman.', // haha
        ]);
    }

    public function testGetSongInfo()
    {
        $song = Song::first();

        $this->actingAs(factory(User::class, 'admin')->create())
            ->get("/api/{$song->id}/info")
            ->seeStatusCode(200)
            ->seeJson([
                'lyrics' => $song->lyrics,
                'artist_info' => false,
                'album_info' => false,
            ]);
    }
}
