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
        $songs = Song::factory()->for($folder)->count(2)->create();

        // create songs in the subfolder, which should not be returned
        Song::factory()->for($subfolder)->create();

        $response = $this->getAs('api/songs/in-folder?path=foo');

        $response->assertJsonStructure([0 => SongFileResource::JSON_STRUCTURE]);
        self::assertEqualsCanonicalizing($songs->pluck('id')->all(), $response->json('*.id'));
    }

    #[Test]
    public function fetchSongsInFolderWithEmptyPath(): void
    {
        /** @var Collection $songs */
        $songs = Song::factory()->count(2)->create();

        $folder = Folder::factory()->create(['path' => 'foo']);

        // create songs in the folder, which should not be returned
        Song::factory()->for($folder)->create();

        $response = $this->getAs('api/songs/in-folder?path=');

        $response->assertJsonStructure([0 => SongFileResource::JSON_STRUCTURE]);
        self::assertEqualsCanonicalizing($songs->pluck('id')->all(), Arr::pluck($response->json(), 'id'));

        $response = $this->getAs('api/songs/in-folder');

        $response->assertJsonStructure([0 => SongFileResource::JSON_STRUCTURE]);
        self::assertEqualsCanonicalizing($songs->pluck('id')->all(), $response->json('*.id'));
    }

    #[Test]
    public function doesNotFetchPrivateSongsFromOtherUsers(): void
    {
        /** @var Collection $songs */
        $songs = Song::factory()->count(2)->create();

        Song::factory()->create(['is_public' => false]);

        $response = $this->getAs('api/songs/in-folder?path=');

        $response->assertJsonStructure([0 => SongFileResource::JSON_STRUCTURE]);
        self::assertEqualsCanonicalizing($songs->pluck('id')->all(), $response->json('*.id'));
    }
}
