<?php

namespace Tests\Feature;

use App\Facades\Dispatcher;
use App\Http\Resources\SongResource;
use App\Jobs\DeleteSongFilesJob;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class SongTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        Song::factory(2)->create();

        $this->getAs('api/songs')->assertJsonStructure(SongResource::PAGINATION_JSON_STRUCTURE);
        $this->getAs('api/songs?sort=title&order=desc')->assertJsonStructure(SongResource::PAGINATION_JSON_STRUCTURE);
    }

    #[Test]
    public function show(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create();

        $this->getAs("api/songs/{$song->id}")->assertJsonStructure(SongResource::JSON_STRUCTURE);
    }

    #[Test]
    public function destroy(): void
    {
        Bus::fake();
        Dispatcher::expects('dispatch')->with(DeleteSongFilesJob::class);

        $songs = Song::factory(2)->create();

        $this->deleteAs('api/songs', ['songs' => $songs->modelKeys()], create_admin())
            ->assertNoContent();

        $songs->each(fn (Song $song) => $this->assertModelMissing($song));
    }

    #[Test]
    public function unauthorizedDelete(): void
    {
        Bus::fake();
        Dispatcher::expects('dispatch')->never();

        $songs = Song::factory(2)->create();

        $this->deleteAs('api/songs', ['songs' => $songs->modelKeys()])
            ->assertForbidden();

        $songs->each(fn (Song $song) => $this->assertModelExists($song));
    }

    #[Test]
    public function singleUpdateAllInfoNoCompilation(): void
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

        $this->assertDatabaseHas(Song::class, [
            'id' => $song->id,
            'album_id' => $album->id,
            'lyrics' => 'Lorem ipsum dolor sic amet.',
            'track' => 1,
            'disc' => 2,
        ]);
    }

    #[Test]
    public function singleUpdateSomeInfoNoCompilation(): void
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

    #[Test]
    public function multipleUpdateNoCompilation(): void
    {
        $songIds = Song::factory(2)->create()->modelKeys();

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

        /** @var Collection<array-key, Song> $songs */
        $songs = Song::query()->whereIn('id', $songIds)->get();

        // All of these songs must now belong to a new album and artist set
        self::assertSame('One by One', $songs[0]->album->name);
        self::assertSame($songs[0]->album_id, $songs[1]->album_id);

        self::assertSame('John Cena', $songs[0]->artist->name);
        self::assertSame($songs[0]->artist_id, $songs[1]->artist_id);

        // Since the lyrics and title were not set, they should be left unchanged
        self::assertNotSame($songs[0]->title, $songs[1]->title);
        self::assertNotSame($songs[0]->lyrics, $songs[1]->lyrics);

        self::assertSame(9999, $songs[0]->track);
        self::assertSame(9999, $songs[1]->track);
    }

    #[Test]
    public function multipleUpdateCreatingNewAlbumsAndArtists(): void
    {
        $originalSongs = Song::factory(2)->create();
        $originalSongIds = $originalSongs->modelKeys();
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

        $songs = Song::query()->whereIn('id', $originalSongIds)->get()->orderByArray($originalSongIds);

        // Even though the album name doesn't change, a new artist should have been created
        // and thus, a new album with the same name was created as well.
        collect([0, 1])->each(static function (int $i) use ($songs, $originalAlbumNames, $originalAlbumIds): void {
            self::assertSame($songs[$i]->album->name, $originalAlbumNames[$i]);
            self::assertNotSame($songs[$i]->album_id, $originalAlbumIds[$i]);
        });

        // And of course, the new artist is...
        self::assertSame('John Cena', $songs[0]->artist->name); // JOHN CENA!!!
        self::assertSame('John Cena', $songs[1]->artist->name); // And... JOHN CENAAAAAAAAAAA!!!
    }

    #[Test]
    public function singleUpdateAllInfoWithCompilation(): void
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

        $this->assertDatabaseHas(Song::class, [
            'id' => $song->id,
            'artist_id' => $artist->id,
            'album_id' => $album->id,
            'lyrics' => 'Lorem ipsum dolor sic amet.',
            'track' => 1,
            'disc' => 2,
        ]);

        self::assertTrue($album->artist->is($albumArtist));
    }

    #[Test]
    public function updateSingleSongWithEmptyTrackAndDisc(): void
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
}
