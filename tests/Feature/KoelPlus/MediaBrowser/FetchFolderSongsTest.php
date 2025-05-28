<?php

namespace Tests\Feature\KoelPlus\MediaBrowser;

use App\Http\Resources\SongFileResource;
use App\Models\Folder;
use App\Models\Setting;
use App\Models\Song;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class FetchFolderSongsTest extends PlusTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Setting::set('media_path', '/var/media');
    }

    #[Test]
    public function fetchSongsInFolderWithAValidPath(): void
    {
        $folder = Folder::factory()->create(['path' => 'foo']);
        $subfolder = Folder::factory()->for($folder, 'parent')->create(['path' => 'foo/bar']);

        /** @var Collection $songs */
        $songs = Song::factory()->for($folder)->count(3)->create();

        // create songs in the subfolder, which should not be returned
        Song::factory()->for($subfolder)->count(4)->create();

        $response = $this->getAs('api/songs/in-folder?path=foo');

        $response->assertJsonStructure(['*' => SongFileResource::JSON_STRUCTURE]);
        self::assertEqualsCanonicalizing($songs->pluck('id')->all(), $response->json('*.id'));
    }

    #[Test]
    public function fetchSongsInFolderWithEmptyPath(): void
    {
        /** @var Collection $songs */
        $songs = Song::factory()->count(3)->create();

        $folder = Folder::factory()->create(['path' => 'foo']);

        // create songs in the folder, which should not be returned
        Song::factory()->for($folder)->count(3)->create();

        $response = $this->getAs('api/songs/in-folder?path=');

        $response->assertJsonStructure(['*' => SongFileResource::JSON_STRUCTURE]);
        self::assertEqualsCanonicalizing($songs->pluck('id')->all(), Arr::pluck($response->json(), 'id'));

        $response = $this->getAs('api/songs/in-folder');

        $response->assertJsonStructure(['*' => SongFileResource::JSON_STRUCTURE]);
        self::assertEqualsCanonicalizing($songs->pluck('id')->all(), $response->json('*.id'));
    }

    #[Test]
    public function doesNotFetchPrivateSongsFromOtherUsers(): void
    {
        /** @var Collection $songs */
        $songs = Song::factory()->count(3)->create();

        Song::factory()->count(2)->create(['is_public' => false]);

        $response = $this->getAs('api/songs/in-folder?path=');

        $response->assertJsonStructure(['*' => SongFileResource::JSON_STRUCTURE]);
        self::assertEqualsCanonicalizing($songs->pluck('id')->all(), $response->json('*.id'));
    }
}
