<?php

namespace Tests\Feature\Subsonic;

use App\Models\Artist;
use App\Services\Contracts\Encyclopedia;
use App\Values\Artist\ArtistInformation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetArtistInfo2Test extends TestCase
{
    #[Test]
    public function returnsBiographyFromEncyclopedia(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne(['user_id' => $user->id]);

        $this
            ->mock(Encyclopedia::class)
            ->expects('getArtistInformation')
            ->andReturn(ArtistInformation::make(
                url: 'https://www.last.fm/music/Foo',
                image: 'https://example.test/foo.jpg',
                bio: ['summary' => 'Short bio', 'full' => 'Full bio'],
            ));

        $this
            ->getJson("/rest/getArtistInfo2.view?apiKey={$user->subsonic_api_key}&f=json&id={$artist->id}")
            ->assertOk()
            ->assertJsonPath('subsonic-response.artistInfo2.biography', 'Short bio')
            ->assertJsonPath('subsonic-response.artistInfo2.lastFmUrl', 'https://www.last.fm/music/Foo')
            ->assertJsonPath('subsonic-response.artistInfo2.smallImageUrl', 'https://example.test/foo.jpg');
    }

    #[Test]
    public function returnsEmptyWhenEncyclopediaReturnsNull(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne(['user_id' => $user->id]);

        $this->mock(Encyclopedia::class)->expects('getArtistInformation')->andReturnNull();

        $response = $this->getJson(
            "/rest/getArtistInfo2.view?apiKey={$user->subsonic_api_key}&f=json&id={$artist->id}",
        )->assertOk();

        // Raw body, not assertJsonPath: json_decode() maps {} and [] to the same PHP value.
        self::assertStringContainsString('"artistInfo2":{}', $response->getContent());
    }

    #[Test]
    public function returnsEmptyArtistInfo2ElementInXmlWhenEncyclopediaReturnsNull(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne(['user_id' => $user->id]);

        $this->mock(Encyclopedia::class)->expects('getArtistInformation')->andReturnNull();

        $response = $this->get(
            "/rest/getArtistInfo2.view?apiKey={$user->subsonic_api_key}&id={$artist->id}",
        )->assertOk();

        $xml = simplexml_load_string($response->getContent());

        self::assertCount(1, $xml->artistInfo2);
    }
}
