<?php

namespace Tests\Feature\Subsonic;

use App\Models\Album;
use App\Services\Integrations\EncyclopediaService;
use App\Values\Album\AlbumInformation;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetAlbumInfoTest extends TestCase
{
    #[Test]
    public function returnsEmptyAlbumInfoObjectWhenThereIsNoInformation(): void
    {
        $user = create_user();
        $album = Album::factory()->for($user)->createOne();

        $this->mock(EncyclopediaService::class)->expects('getAlbumInformation')->andReturn(AlbumInformation::make());

        $response = $this->getJson(
            '/rest/getAlbumInfo.view?'
                . Arr::query([
                    'apiKey' => $user->subsonic_api_key,
                    'f' => 'json',
                    'id' => $album->id,
                ]),
        )->assertOk();

        self::assertStringContainsString('"albumInfo":{}', $response->getContent());
    }
}
