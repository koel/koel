<?php

namespace Tests\Feature\KoelPlus\Subsonic;

use App\Models\Album;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\test_path;

class GetCoverArtTest extends PlusTestCase
{
    private string $stagedPath;

    public function setUp(): void
    {
        parent::setUp();

        $this->stagedPath = image_storage_path('sub_plus_cover_test.png');
        File::copy(test_path('fixtures/cover.png'), $this->stagedPath);
    }

    protected function tearDown(): void
    {
        File::delete($this->stagedPath);

        parent::tearDown();
    }

    #[Test]
    public function otherUsersCoverReturnsCode70(): void
    {
        $owner = create_user();
        $requester = create_user();

        $album = Album::factory()->createOne([
            'cover' => basename($this->stagedPath),
            'user_id' => $owner->id,
        ]);

        $this
            ->getJson("/rest/getCoverArt.view?apiKey={$requester->subsonic_api_key}&f=json&id={$album->id}")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);
    }
}
