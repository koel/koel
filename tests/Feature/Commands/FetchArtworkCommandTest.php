<?php

namespace Tests\Feature\Commands;

use App\Models\Album;
use App\Models\Artist;
use App\Services\Integrations\EncyclopediaService;
use Illuminate\Support\Facades\Cache;
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
    public function forgetCachedAlbumInformationBeforeFetching(): void
    {
        config([
            'koel.services.spotify.client_id' => 'fake-id',
            'koel.services.spotify.client_secret' => 'fake-secret',
        ]);

        $album = Album::factory()->createOne(['cover' => '']);
        $cacheKey = cache_key('album information', $album->name, $album->artist->name);
        Cache::put($cacheKey, 'stale');

        $encyclopedia = Mockery::mock(EncyclopediaService::class);
        $encyclopedia->shouldReceive('getArtistInformation');
        $encyclopedia->shouldReceive('getAlbumInformation')->once();

        $this->app->instance(EncyclopediaService::class, $encyclopedia);

        $this->artisan('koel:fetch-artwork', ['--delay' => 0])->assertSuccessful();

        self::assertFalse(Cache::has($cacheKey));
    }
}
