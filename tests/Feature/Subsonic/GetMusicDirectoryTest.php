<?php

namespace Tests\Feature\Subsonic;

use App\Http\Responses\Subsonic\Resources\AlbumChildResource;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetMusicDirectoryTest extends TestCase
{
    #[Test]
    public function artistIdReturnsItsAlbumsAsChildDirectories(): void
    {
        $user = create_user();

        $artist = Artist::factory()->createOne(['name' => 'Radiohead', 'user_id' => $user->id]);
        Album::factory()->createMany([
            [
                'name' => 'OK Computer',
                'artist_id' => $artist->id,
                'artist_name' => $artist->name,
                'user_id' => $user->id,
            ],
            [
                'name' => 'In Rainbows',
                'artist_id' => $artist->id,
                'artist_name' => $artist->name,
                'user_id' => $user->id,
            ],
        ]);

        $response = $this
            ->getJson(
                '/rest/getMusicDirectory.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                        'id' => $artist->id,
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.directory.id', $artist->id)
            ->assertJsonPath('subsonic-response.directory.name', $artist->name)
            ->assertJsonStructure([
                'subsonic-response' => [
                    'directory' => [
                        'child' => ['*' => AlbumChildResource::JSON_STRUCTURE],
                    ],
                ],
            ]);

        $children = $response->json('subsonic-response.directory.child') ?? [];
        $names = array_column($children, 'title');

        self::assertContains('OK Computer', $names);
        self::assertContains('In Rainbows', $names);

        foreach ($children as $child) {
            self::assertTrue($child['isDir']);
            self::assertSame($artist->id, $child['parent']);
        }
    }

    #[Test]
    public function albumIdReturnsItsSongsAsChildren(): void
    {
        $user = create_user();

        $album = Album::factory()->createOne(['name' => 'Kid A', 'user_id' => $user->id]);
        Song::factory()->createMany([
            ['title' => 'Everything In Its Right Place', 'album_id' => $album->id, 'owner_id' => $user->id],
            ['title' => 'The National Anthem', 'album_id' => $album->id, 'owner_id' => $user->id],
        ]);

        $response = $this
            ->getJson(
                '/rest/getMusicDirectory.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                        'id' => $album->id,
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.directory.id', $album->id)
            ->assertJsonPath('subsonic-response.directory.parent', $album->artist_id)
            ->assertJsonPath('subsonic-response.directory.name', $album->name)
            ->assertJsonStructure([
                'subsonic-response' => [
                    'directory' => [
                        'child' => ['*' => SongResource::JSON_STRUCTURE],
                    ],
                ],
            ]);

        $children = $response->json('subsonic-response.directory.child') ?? [];
        $titles = array_column($children, 'title');

        self::assertContains('Everything In Its Right Place', $titles);
        self::assertContains('The National Anthem', $titles);

        foreach ($children as $child) {
            self::assertFalse($child['isDir']);
        }
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getMusicDirectory.view?apiKey={$user->subsonic_api_key}&f=json&id=does-not-exist")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);
    }
}
