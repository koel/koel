<?php

namespace Tests\Feature\KoelPlus\MediaBrowser;

use App\Http\Resources\SongFileResource;
use App\Models\Folder;
use App\Models\Song;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class PaginateFolderSongsTest extends PlusTestCase
{
    #[Test]
    public function paginate(): void
    {
        /** @var Folder $folder */
        $folder = Folder::factory()->create(['path' => 'foo/bar']);

        /** @var Collection<Song> $songs */
        $songs = Song::factory()->for($folder)->count(2)->create();

        $response = $this->getAs('/api/browse/songs?path=foo/bar&page=1')
            ->assertJsonStructure(SongFileResource::PAGINATION_JSON_STRUCTURE);

        self::assertEqualsCanonicalizing($songs->pluck('id')->all(), $response->json('data.*.id'));
    }

    #[Test]
    public function paginateRootMediaFolder(): void
    {
        /** @var Collection<Song> $songs */
        $songs = Song::factory()->count(2)->create();

        $response = $this->getAs('/api/browse/songs')
            ->assertJsonStructure(SongFileResource::PAGINATION_JSON_STRUCTURE);

        self::assertEqualsCanonicalizing($songs->pluck('id')->all(), $response->json('data.*.id'));
    }
}
