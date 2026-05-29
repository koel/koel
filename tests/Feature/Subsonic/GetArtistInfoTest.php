<?php

namespace Tests\Feature\Subsonic;

use App\Models\Artist;
use App\Services\Contracts\Encyclopedia;
use App\Values\Artist\ArtistInformation;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetArtistInfoTest extends TestCase
{
    #[Test]
    public function returnsBiographyFromEncyclopediaUnderArtistInfoWrapper(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne(['user_id' => $user->id]);

        $this
            ->mock(Encyclopedia::class)
            ->expects('getArtistInformation')
            ->andReturn(ArtistInformation::make(
                url: 'https://www.last.fm/artist/Foo',
                image: 'https://example.test/artist.jpg',
                bio: ['summary' => 'About the artist', 'full' => 'Full text'],
            ));

        $this
            ->getJson(
                '/rest/getArtistInfo.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                        'id' => $artist->id,
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.artistInfo.biography', 'About the artist')
            ->assertJsonPath('subsonic-response.artistInfo.lastFmUrl', 'https://www.last.fm/artist/Foo')
            ->assertJsonPath('subsonic-response.artistInfo.smallImageUrl', 'https://example.test/artist.jpg');
    }

    #[Test]
    public function returnsEmptyArtistInfoWhenEncyclopediaReturnsNull(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne(['user_id' => $user->id]);

        $this->mock(Encyclopedia::class)->expects('getArtistInformation')->andReturnNull();

        $response = $this->getJson(
            '/rest/getArtistInfo.view?'
                . Arr::query([
                    'apiKey' => $user->subsonic_api_key,
                    'f' => 'json',
                    'id' => $artist->id,
                ]),
        )->assertOk();

        self::assertSame([], $response->json('subsonic-response.artistInfo'));
    }
}
