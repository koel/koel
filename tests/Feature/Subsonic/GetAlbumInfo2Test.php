<?php

namespace Tests\Feature\Subsonic;

use App\Models\Album;
use App\Services\Contracts\Encyclopedia;
use App\Values\Album\AlbumInformation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetAlbumInfo2Test extends TestCase
{
    #[Test]
    public function returnsNotesFromEncyclopedia(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne(['user_id' => $user->id]);

        $this
            ->mock(Encyclopedia::class)
            ->expects('getAlbumInformation')
            ->andReturn(AlbumInformation::make(
                url: 'https://www.last.fm/album/Foo',
                cover: 'https://example.test/cover.jpg',
                wiki: ['summary' => 'About the album', 'full' => 'Full text'],
            ));

        $this
            ->getJson("/rest/getAlbumInfo2.view?apiKey={$user->subsonic_api_key}&f=json&id={$album->id}")
            ->assertOk()
            ->assertJsonPath('subsonic-response.albumInfo.notes', 'About the album')
            ->assertJsonPath('subsonic-response.albumInfo.lastFmUrl', 'https://www.last.fm/album/Foo')
            ->assertJsonPath('subsonic-response.albumInfo.smallImageUrl', 'https://example.test/cover.jpg');
    }

    #[Test]
    public function returnsEmptyWhenEncyclopediaReturnsNull(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne(['user_id' => $user->id]);

        $this->mock(Encyclopedia::class)->expects('getAlbumInformation')->andReturnNull();

        $this
            ->getJson("/rest/getAlbumInfo2.view?apiKey={$user->subsonic_api_key}&f=json&id={$album->id}")
            ->assertOk()
            ->assertJsonPath('subsonic-response.albumInfo', []);
    }
}
