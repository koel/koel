<?php

namespace Tests\Unit;

use App\Models\Song;
use Cache;
use MediaCache;
use Tests\TestCase;

class MediaCacheTest extends TestCase
{
    /** @test */
    public function it_queries_fresh_data_from_database_if_a_cache_is_not_found()
    {
        Cache::shouldReceive('get')->andReturnNull();
        Cache::shouldReceive('forever')->once();

        // Given a database with artists, albums, and songs
        factory(Song::class, 5)->create();

        // When I get data from the MediaCache service
        $data = MediaCache::get();

        // Then a complete set of data is retrieved
        $this->assertCount(6, $data['albums']); // 5 new albums and the default Unknown Album
        $this->assertCount(7, $data['artists']); // 5 new artists and the default Various and Unknown Artist
        $this->assertCount(5, $data['songs']);
    }

    /** @test */
    public function it_get_the_cached_data_if_found()
    {
        // Given there are media data cached
        Cache::shouldReceive('get')->andReturn('dummy');

        // And koel.cache_media configuration is TRUE
        config(['koel.cache_media' => true]);

        // When I get data from the MediaCache service
        $data = MediaCache::get();

        // Then I receive the cached version
        $this->assertEquals('dummy', $data);
    }

    /** @test */
    public function it_caches_queried_data_if_cache_media_is_configured_to_true()
    {
        Cache::shouldReceive('forever')->once();
        Cache::shouldReceive('get')->once()->andReturnNull();

        // Given koel.cache_media configuration is TRUE
        config(['koel.cache_media' => true]);

        // When I get data from the MediaCache service
        MediaCache::get();

        // Then I see the cache-related methods being called
    }

    /** @test */
    public function it_does_not_cache_queried_data_if_cache_media_is_configured_to_false()
    {
        Cache::shouldReceive('forever')->never();

        // Given koel.cache_media configuration is FALSE
        config(['koel.cache_media' => false]);

        // When I get data from the MediaCache service
        MediaCache::get();

        // Then I don't see the cache-related methods being called
    }
}
