<?php

namespace Tests\Feature;

use App\Events\LibraryChanged;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Exception;

class SongTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        static::createSampleMediaSet();
    }

    /**
     * @throws Exception
     */
    public function testSingleUpdateAllInfoNoCompilation(): void
    {
        $this->expectsEvents(LibraryChanged::class);
        $song = Song::orderBy('id', 'desc')->first();

        $user = factory(User::class)->states('admin')->create();
        $this->putAsUser('/api/songs', [
            'songs' => [$song->id],
            'data' => [
                'title' => 'Foo Bar',
                'artistName' => 'John Cena',
                'albumName' => 'One by One',
                'lyrics' => 'Lorem ipsum dolor sic amet.',
                'track' => 1,
                'compilationState' => 0,
            ],
        ], $user)
            ->assertStatus(200);

        $artist = Artist::where('name', 'John Cena')->first();
        self::assertNotNull($artist);

        $album = Album::where('name', 'One by One')->first();
        self::assertNotNull($album);

        self::assertDatabaseHas('songs', [
            'id' => $song->id,
            'album_id' => $album->id,
            'lyrics' => 'Lorem ipsum dolor sic amet.',
            'track' => 1,
        ]);
    }

    public function testSingleUpdateSomeInfoNoCompilation(): void
    {
        $song = Song::orderBy('id', 'desc')->first();
        $originalArtistId = $song->album->artist->id;

        $user = factory(User::class)->states('admin')->create();
        $this->putAsUser('/api/songs', [
            'songs' => [$song->id],
            'data' => [
                'title' => '',
                'artistName' => '',
                'albumName' => 'One by One',
                'lyrics' => 'Lorem ipsum dolor sic amet.',
                'track' => 1,
                'compilationState' => 0,
            ],
        ], $user)
            ->assertStatus(200);

        // We don't expect the song's artist to change
        self::assertEquals($originalArtistId, Song::find($song->id)->album->artist->id);

        // But we expect a new album to be created for this artist and contain this song
        self::assertEquals('One by One', Song::find($song->id)->album->name);
    }

    public function testMultipleUpdateAllInfoNoCompilation(): void
    {
        $songIds = Song::orderBy('id', 'desc')->take(3)->pluck('id')->toArray();

        $user = factory(User::class)->states('admin')->create();
        $this->putAsUser('/api/songs', [
            'songs' => $songIds,
            'data' => [
                'title' => 'foo',
                'artistName' => 'John Cena',
                'albumName' => 'One by One',
                'lyrics' => 'bar',
                'track' => 9999,
                'compilationState' => 0,
            ],
        ], $user)
            ->assertStatus(200);

        $songs = Song::orderBy('id', 'desc')->take(3)->get();

        // Even though we post the title, lyrics, and tracks, we don't expect them to take any effect
        // because we're updating multiple songs here.
        self::assertNotEquals('foo', $songs[0]->title);
        self::assertNotEquals('bar', $songs[2]->lyrics);
        self::assertNotEquals(9999, $songs[2]->track);

        // But all of these songs must now belong to a new album and artist set
        self::assertEquals('One by One', $songs[0]->album->name);
        self::assertEquals('One by One', $songs[1]->album->name);
        self::assertEquals('One by One', $songs[2]->album->name);

        self::assertEquals('John Cena', $songs[0]->album->artist->name);
        self::assertEquals('John Cena', $songs[1]->album->artist->name);
        self::assertEquals('John Cena', $songs[2]->album->artist->name);
    }

    public function testMultipleUpdateSomeInfoNoCompilation(): void
    {
        $originalSongs = Song::orderBy('id', 'desc')->take(3)->get();
        $songIds = $originalSongs->pluck('id')->toArray();

        $user = factory(User::class)->states('admin')->create();
        $this->putAsUser('/api/songs', [
            'songs' => $songIds,
            'data' => [
                'title' => 'Foo Bar',
                'artistName' => 'John Cena',
                'albumName' => '',
                'lyrics' => 'Lorem ipsum dolor sic amet.',
                'track' => 1,
                'compilationState' => 0,
            ],
        ], $user)
            ->assertStatus(200);

        $songs = Song::orderBy('id', 'desc')->take(3)->get();

        // Even though the album name doesn't change, a new artist should have been created
        // and thus, a new album with the same name was created as well.
        self::assertEquals($songs[0]->album->name, $originalSongs[0]->album->name);
        self::assertNotEquals($songs[0]->album->id, $originalSongs[0]->album->id);
        self::assertEquals($songs[1]->album->name, $originalSongs[1]->album->name);
        self::assertNotEquals($songs[1]->album->id, $originalSongs[1]->album->id);
        self::assertEquals($songs[2]->album->name, $originalSongs[2]->album->name);
        self::assertNotEquals($songs[2]->album->id, $originalSongs[2]->album->id);

        // And of course, the new artist is...
        self::assertEquals('John Cena', $songs[0]->album->artist->name); // JOHN CENA!!!
        self::assertEquals('John Cena', $songs[1]->album->artist->name); // JOHN CENA!!!
        self::assertEquals('John Cena', $songs[2]->album->artist->name); // And... JOHN CENAAAAAAAAAAA!!!
    }

    public function testSingleUpdateAllInfoYesCompilation(): void
    {
        $song = Song::orderBy('id', 'desc')->first();

        $user = factory(User::class)->states('admin')->create();
        $this->putAsUser('/api/songs', [
            'songs' => [$song->id],
            'data' => [
                'title' => 'Foo Bar',
                'artistName' => 'John Cena',
                'albumName' => 'One by One',
                'lyrics' => 'Lorem ipsum dolor sic amet.',
                'track' => 1,
                'compilationState' => 1,
            ],
        ], $user)
            ->assertStatus(200);

        $compilationAlbum = Album::whereArtistIdAndName(Artist::VARIOUS_ID, 'One by One')->first();
        self::assertNotNull($compilationAlbum);

        $artist = Artist::whereName('John Cena')->first();
        self::assertNotNull($artist);

        self::assertDatabaseHas('songs', [
            'id' => $song->id,
            'artist_id' => $artist->id,
            'album_id' => $compilationAlbum->id,
            'lyrics' => 'Lorem ipsum dolor sic amet.',
            'track' => 1,
        ]);

        // Now try changing stuff and make sure things work.
        // Case 1: Keep compilation state and artist the same
        $this->putAsUser('/api/songs', [
            'songs' => [$song->id],
            'data' => [
                'title' => 'Barz Qux',
                'artistName' => 'John Cena',
                'albumName' => 'Two by Two',
                'lyrics' => 'Lorem ipsum dolor sic amet.',
                'track' => 1,
                'compilationState' => 2,
            ],
        ], $user)
            ->assertStatus(200);

        /** @var Album $compilationAlbum */
        $compilationAlbum = Album::where([
            'artist_id' => Artist::VARIOUS_ID,
            'name' => 'Two by Two',
        ])->first();

        self::assertNotNull($compilationAlbum);

        $contributingArtist = Artist::where('name', 'John Cena')->first();
        self::assertNotNull($contributingArtist);

        self::assertDatabaseHas('songs', [
            'id' => $song->id,
            'artist_id' => $contributingArtist->id,
            'album_id' => $compilationAlbum->id,
        ]);

        // Case 2: Keep compilation state, but change the artist.
        $this->putAsUser('/api/songs', [
            'songs' => [$song->id],
            'data' => [
                'title' => 'Barz Qux',
                'artistName' => 'Foo Fighters',
                'albumName' => 'One by One',
                'lyrics' => 'Lorem ipsum dolor sic amet.',
                'track' => 1,
                'compilationState' => 2,
            ],
        ], $user)
            ->assertStatus(200);

        $compilationAlbum = Album::where([
            'artist_id' => Artist::VARIOUS_ID,
            'name' => 'One by One',
        ])->first();
        self::assertNotNull($compilationAlbum);

        /** @var Artist $contributingArtist */
        $contributingArtist = Artist::where('name', 'Foo Fighters')->first();
        self::assertNotNull($contributingArtist);

        self::assertDatabaseHas('songs', [
            'id' => $song->id,
            'artist_id' => $contributingArtist->id,
            'album_id' => $compilationAlbum->id,
        ]);

        // Case 3: Change compilation state only
        $this->putAsUser('/api/songs', [
            'songs' => [$song->id],
            'data' => [
                'title' => 'Barz Qux',
                'artistName' => 'Foo Fighters',
                'albumName' => 'One by One',
                'lyrics' => 'Lorem ipsum dolor sic amet.',
                'track' => 1,
                'compilationState' => 0,
            ],
        ], $user)
            ->assertStatus(200);

        /** @var Artist $artist */
        $artist = Artist::where('name', 'Foo Fighters')->first();
        self::assertNotNull($artist);

        $album = Album::where([
            'artist_id' => $artist->id,
            'name' => 'One by One',
        ])->first();
        self::assertNotNull($album);

        self::assertDatabaseHas('songs', [
            'id' => $song->id,
            'artist_id' => $artist->id,
            'album_id' => $album->id,
        ]);

        // Case 3: Change compilation state and artist
        // Remember to set the compilation state back to 1
        $this->putAsUser('/api/songs', [
            'songs' => [$song->id],
            'data' => [
                'title' => 'Barz Qux',
                'artistName' => 'Foo Fighters',
                'albumName' => 'One by One',
                'lyrics' => 'Lorem ipsum dolor sic amet.',
                'track' => 1,
                'compilationState' => 1,
            ],
        ], $user);

        $this->putAsUser('/api/songs', [
            'songs' => [$song->id],
            'data' => [
                'title' => 'Twilight of the Thunder God',
                'artistName' => 'Amon Amarth',
                'albumName' => 'Twilight of the Thunder God',
                'lyrics' => 'Thor! Nanananananana Batman.',
                'track' => 1,
                'compilationState' => 0,
            ],
        ], $user)
            ->assertStatus(200);

        $artist = Artist::where('name', 'Amon Amarth')->first();
        self::assertNotNull($artist);
        $album = Album::where([
            'artist_id' => $artist->id,
            'name' => 'Twilight of the Thunder God',
        ])->first();
        self::assertNotNull($album);
        self::assertDatabaseHas('songs', [
            'id' => $song->id,
            'artist_id' => $artist->id,
            'album_id' => $album->id,
            'lyrics' => 'Thor! Nanananananana Batman.', // haha
        ]);
    }

    public function testDeletingByChunk(): void
    {
        self::assertNotEquals(0, Song::count());
        $ids = Song::select('id')->get()->pluck('id')->all();
        Song::deleteByChunk($ids, 'id', 1);
        self::assertEquals(0, Song::count());
    }
}
