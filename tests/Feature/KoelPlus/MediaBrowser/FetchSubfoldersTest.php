<?php

namespace Tests\Feature\KoelPlus\MediaBrowser;

use App\Http\Resources\FolderResource;
use App\Models\Folder;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class FetchSubfoldersTest extends PlusTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Setting::set('media_path', '/var/media');
    }

    #[Test]
    public function testFetch(): void
    {
        /** @var Folder $folder */
        $folder = Folder::factory()->create();

        /** @var Collection $subfolders */
        $subfolders = Folder::factory()->for($folder, 'parent')->count(2)->create();

        $response = $this->getAs('/api/browse/folders?path=' . $folder->path)
            ->assertJsonStructure([0 => FolderResource::JSON_STRUCTURE]);

        self::assertEqualsCanonicalizing($subfolders->pluck('id')->toArray(), $response->json('*.id'));
    }

    #[Test]
    public function tesFetchUnderMediaRoot(): void
    {
        $subfolders = Folder::factory()->count(2)->create();

        $response = $this->getAs('/api/browse/folders')
            ->assertJsonStructure([0 => FolderResource::JSON_STRUCTURE]);

        self::assertEqualsCanonicalizing($subfolders->pluck('id')->toArray(), $response->json('*.id'));
    }
}
