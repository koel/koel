<?php

namespace Tests\Feature\Commands;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\ThrottledMusicBrainzConnector;
use App\Models\Album;
use App\Models\Artist;
use App\Services\Integrations\EncyclopediaService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FetchArtworkCommandTest extends TestCase
{
    #[Test]
    public function failWhenNoServiceIsConfigured(): void
    {
        config([
            'koel.services.spotify.client_id' => null,
            'koel.services.spotify.client_secret' => null,
            'koel.services.musicbrainz.enabled' => false,
        ]);

        $this->artisan('koel:fetch-artwork')->assertFailed();
    }

    #[Test]
    public function fetchArtwork(): void
    {
        config([
            'koel.services.spotify.client_id' => 'fake-id',
            'koel.services.spotify.client_secret' => 'fake-secret',
        ]);

        Artist::factory()->createOne(['image' => '']);
        Album::factory()->createOne(['cover' => '']);

        $encyclopedia = Mockery::mock(EncyclopediaService::class);
        $encyclopedia->shouldReceive('getArtistInformation')->atLeast()->once();
        $encyclopedia->shouldReceive('getAlbumInformation')->atLeast()->once();

        $this->app->instance(EncyclopediaService::class, $encyclopedia);

        $this->artisan('koel:fetch-artwork')->assertSuccessful();
    }

    #[Test]
    public function throttleTheLookupsMusicBrainzReceives(): void
    {
        config(['koel.services.musicbrainz.enabled' => true]);

        $encyclopedia = Mockery::mock(EncyclopediaService::class);
        $encyclopedia->allows('getArtistInformation');
        $encyclopedia->allows('getAlbumInformation');

        $this->app->instance(EncyclopediaService::class, $encyclopedia);

        $this->artisan('koel:fetch-artwork', ['--delay' => 0])->assertSuccessful();

        self::assertInstanceOf(ThrottledMusicBrainzConnector::class, app(MusicBrainzConnector::class));
    }
}
