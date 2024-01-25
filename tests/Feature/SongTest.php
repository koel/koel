<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Support\Collection;
use Tests\TestCase;

use function Tests\create_admin;

class SongTest extends TestCase
{
    public function testIndex(): void
    {
        Song::factory(10)->create();

        $this->getAs('api/songs')->assertJsonStructure(SongResource::PAGINATION_JSON_STRUCTURE);
        $this->getAs('api/songs?sort=title&order=desc')->assertJsonStructure(SongResource::PAGINATION_JSON_STRUCTURE);
    }

    public function testShow(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create();

        $this->getAs('api/songs/' . $song->id)->assertJsonStructure(SongResource::JSON_STRUCTURE);
    }

    public function testDelete(): void
    {
        /** @var Collection|array<array-key, Song> $songs */
        $songs = Song::factory(3)->create();

        $this->deleteAs('api/songs', ['songs' => $songs->pluck('id')->all()], create_admin())
            ->assertNoContent();

        $songs->each(fn (Song $song) => $this->assertModelMissing($song));
    }

    public function testUnauthorizedDelete(): void
    {
        /** @var Collection|array<array-key, Song> $songs */
        $songs = Song::factory(3)->create();

        $this->deleteAs('api/songs', ['songs' => $songs->pluck('id')->all()])
            ->assertForbidden();

        $songs->each(fn (Song $song) => $this->assertModelExists($song));
    }

    public function testSingleUpdateAllInfoNoCompilation(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create();

        $this->putAs('/api/songs', [
            'songs' => [$song->id],
            'data' => [
                'title' => 'Foo Bar',
                'artist_name' => 'John Cena',
                'album_name' => 'One by One',
                'lyrics' => 'Lorem ipsum dolor sic amet.',
                'track' => 1,
                'disc' => 2,
            ],
        ], create_admin())
            ->assertOk();

        /** @var Artist $artist */
        $artist = Artist::query()->where('name', 'John Cena')->first();
        self::assertNotNull($artist);

        /** @var Album $album */
        $album = Album::query()->where('name', 'One by One')->first();
        self::assertNotNull($album);

        self::assertDatabaseHas(Song::class, [
            'id' => $song->id,
            'album_id' => $album->id,
            'lyrics' => 'Lorem ipsum dolor sic amet.',
            'track' => 1,
            'disc' => 2,
        ]);
    }

    public function testSingleUpdateSomeInfoNoCompilation(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create();

        $originalArtistId = $song->artist->id;

        $this->putAs('/api/songs', [
            'songs' => [$song->id],
            'data' => [
                'title' => '',
                'artist_name' => '',
                'album_name' => 'One by One',
                'lyrics' => 'Lorem ipsum dolor sic amet.',
                'track' => 1,
            ],
        ], create_admin())
            ->assertOk();

        // We don't expect the song's artist to change
        self::assertSame($originalArtistId, $song->refresh()->artist->id);

        // But we expect a new album to be created for this artist and contain this song
        self::assertSame('One by One', $song->album->name);
    }

    public function testMultipleUpdateNoCompilation(): void
    {
        $songIds = Song::factory(3)->create()->pluck('id')->all();

        $this->putAs('/api/songs', [
            'songs' => $songIds,
            'data' => [
                'title' => null,
                'artist_name' => 'John Cena',
                'album_name' => 'One by One',
                'lyrics' => null,
                'track' => 9999,
            ],
        ], create_admin())
            ->assertOk();

        /** @var Collection|array<array-key, Song> $songs */
        $songs = Song::query()->whereIn('id', $songIds)->get();

        // All of these songs must now belong to a new album and artist set
        self::assertSame('One by One', $songs[0]->album->name);
        self::assertSame($songs[0]->album_id, $songs[1]->album_id);
        self::assertSame($songs[0]->album_id, $songs[2]->album_id);

        self::assertSame('John Cena', $songs[0]->artist->name);
        self::assertSame($songs[0]->artist_id, $songs[1]->artist_id);
        self::assertSame($songs[0]->artist_id, $songs[2]->artist_id);

        self::assertNotSame($songs[0]->title, $songs[1]->title);
        self::assertNotSame($songs[0]->lyrics, $songs[1]->lyrics);

        self::assertSame(9999, $songs[0]->track);
        self::assertSame(9999, $songs[1]->track);
        self::assertSame(9999, $songs[2]->track);
    }

    public function testMultipleUpdateCreatingNewAlbumsAndArtists(): void
    {
        /** @var array<array-key, Song>|Collection $originalSongs */
        $originalSongs = Song::factory(3)->create();
        $originalSongIds = $originalSongs->pluck('id')->all();
        $originalAlbumNames = $originalSongs->pluck('album.name')->all();
        $originalAlbumIds = $originalSongs->pluck('album_id')->all();

        $this->putAs('/api/songs', [
            'songs' =>  $originalSongIds,
            'data' => [
                'title' => 'Foo Bar',
                'artist_name' => 'John Cena',
                'album_name' => '',
                'lyrics' => 'Lorem ipsum dolor sic amet.',
                'track' => 1,
            ],
        ], create_admin())
            ->assertOk();

        /** @var array<array-key, Song>|Collection $songs */
        $songs = Song::query()->whereIn('id', $originalSongIds)->get()->orderByArray($originalSongIds);

        // Even though the album name doesn't change, a new artist should have been created
        // and thus, a new album with the same name was created as well.
        collect([0, 1, 2])->each(static function (int $i) use ($songs, $originalAlbumNames, $originalAlbumIds): void {
            self::assertSame($songs[$i]->album->name, $originalAlbumNames[$i]);
            self::assertNotSame($songs[$i]->album_id, $originalAlbumIds[$i]);
        });

        // And of course, the new artist is...
        self::assertSame('John Cena', $songs[0]->artist->name); // JOHN CENA!!!
        self::assertSame('John Cena', $songs[1]->artist->name); // JOHN CENA!!!
        self::assertSame('John Cena', $songs[2]->artist->name); // And... JOHN CENAAAAAAAAAAA!!!
    }

    public function testSingleUpdateAllInfoWithCompilation(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create();

        $this->putAs('/api/songs', [
            'songs' => [$song->id],
            'data' => [
                'title' => 'Foo Bar',
                'artist_name' => 'John Cena',
                'album_name' => 'One by One',
                'album_artist_name' => 'John Lennon',
                'lyrics' => 'Lorem ipsum dolor sic amet.',
                'track' => 1,
                'disc' => 2,
            ],
        ], create_admin())
            ->assertOk();

        /** @var Album $album */
        $album = Album::query()->where('name', 'One by One')->first();

        /** @var Artist $albumArtist */
        $albumArtist = Artist::query()->where('name', 'John Lennon')->first();

        /** @var Artist $artist */
        $artist = Artist::query()->where('name', 'John Cena')->first();

        self::assertDatabaseHas(Song::class, [
            'id' => $song->id,
            'artist_id' => $artist->id,
            'album_id' => $album->id,
            'lyrics' => 'Lorem ipsum dolor sic amet.',
            'track' => 1,
            'disc' => 2,
        ]);

        self::assertTrue($album->artist->is($albumArtist));
    }

    public function testUpdateSingleSongWithEmptyTrackAndDisc(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create([
            'track' => 12,
            'disc' => 2,
        ]);

        $this->putAs('/api/songs', [
            'songs' => [$song->id],
            'data' => [
                'track' => null,
                'disc' => null,
            ],
        ], create_admin())
            ->assertOk();

        $song->refresh();

        self::assertSame(0, $song->track);
        self::assertSame(1, $song->disc);
    }

    public function testDeletingByChunk(): void
    {
        Song::factory(5)->create();

        self::assertNotSame(0, Song::query()->count());
        $ids = Song::query()->select('id')->get()->pluck('id')->all();

        Song::deleteByChunk($ids, 1);

        self::assertSame(0, Song::query()->count());
    }
}
