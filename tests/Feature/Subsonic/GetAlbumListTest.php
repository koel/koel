<?php

namespace Tests\Feature\Subsonic;

use App\Http\Responses\Subsonic\Resources\AlbumChildResource;
use App\Models\Album;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetAlbumListTest extends TestCase
{
    #[Test]
    public function newestReturnsAlbumsAsChildElements(): void
    {
        $user = create_user();

        Album::factory()->createMany([
            ['name' => 'Older', 'user_id' => $user->id, 'created_at' => now()->subDays(10)],
            ['name' => 'Newer', 'user_id' => $user->id, 'created_at' => now()],
        ]);

        $response = $this
            ->getJson(
                '/rest/getAlbumList.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                        'type' => 'newest',
                        'size' => 2,
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertJsonStructure([
                'subsonic-response' => [
                    'albumList' => [
                        'album' => ['*' => AlbumChildResource::JSON_STRUCTURE],
                    ],
                ],
            ]);

        $titles = array_column($response->json('subsonic-response.albumList.album'), 'title');
        self::assertSame(['Newer', 'Older'], $titles);

        foreach ($response->json('subsonic-response.albumList.album') as $child) {
            self::assertTrue($child['isDir']);
        }
    }

    #[Test]
    public function unsupportedTypeReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson(
                '/rest/getAlbumList.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                        'type' => 'bogus',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function missingTypeReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson(
                '/rest/getAlbumList.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }
}
