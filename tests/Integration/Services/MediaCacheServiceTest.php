<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Services\MediaCacheService;
use Illuminate\Cache\Repository as Cache;
use Mockery;
use Tests\TestCase;

class MediaCacheServiceTest extends TestCase
{
    private MediaCacheService $mediaCacheService;
    private $cache;

    public function setUp(): void
    {
        parent::setUp();

        $this->cache = Mockery::mock(Cache::class);
        $this->mediaCacheService = new MediaCacheService($this->cache);
    }

    public function testGetIfCacheIsNotAvailable(): void
    {
        Song::factory(5)->create();

        $this->cache->shouldReceive('rememberForever')->andReturn([
            'albums' => Album::query()->orderBy('name')->get(),
            'artists' => Artist::query()->orderBy('name')->get(),
            'songs' => Song::all(),
        ]);

        $data = $this->mediaCacheService->get();

        self::assertCount(6, $data['albums']); // 5 new albums and the default Unknown Album
        self::assertCount(7, $data['artists']); // 5 new artists and the default Various and Unknown Artist
        self::assertCount(5, $data['songs']);
    }

    public function testGetIfCacheIsAvailable(): void
    {
        $this->cache->shouldReceive('rememberForever')->andReturn(['dummy']);

        config(['koel.cache_media' => true]);

        $data = $this->mediaCacheService->get();

        self::assertEquals(['dummy'], $data);
    }

    public function testCacheDisabled(): void
    {
        $this->cache->shouldReceive('rememberForever')->never();

        config(['koel.cache_media' => false]);

        $this->mediaCacheService->get();
    }
}
