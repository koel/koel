<?php

namespace Tests\Unit\Services;

use App\Models\RadioStation;
use App\Services\RadioStreamService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RadioStreamServiceTest extends TestCase
{
    #[Test]
    public function parseIcyMetadataExtractsStreamTitle(): void
    {
        $metadata = "StreamTitle='Artist - Song Title';StreamUrl='';";
        $result = RadioStreamService::parseIcyMetadata($metadata);

        self::assertSame('Artist - Song Title', $result['stream_title']);
    }

    #[Test]
    public function parseIcyMetadataReturnsNullForEmptyTitle(): void
    {
        $metadata = "StreamTitle='';StreamUrl='';";
        $result = RadioStreamService::parseIcyMetadata($metadata);

        self::assertNull($result['stream_title']);
    }

    #[Test]
    public function parseIcyMetadataReturnsNullForNoMatch(): void
    {
        $metadata = 'no metadata here';
        $result = RadioStreamService::parseIcyMetadata($metadata);

        self::assertNull($result['stream_title']);
    }

    #[Test]
    public function cacheAndGetMetadata(): void
    {
        $station = RadioStation::factory()->create();

        RadioStreamService::cacheMetadata($station, 'Now Playing - Track');
        $cached = RadioStreamService::getCachedMetadata($station);

        self::assertSame('Now Playing - Track', $cached['stream_title']);
        self::assertNotNull($cached['updated_at']);
    }

    #[Test]
    public function getCachedMetadataReturnsDefaultWhenEmpty(): void
    {
        $station = RadioStation::factory()->create();

        Cache::forget(RadioStreamService::cacheKey($station));
        $cached = RadioStreamService::getCachedMetadata($station);

        self::assertNull($cached['stream_title']);
        self::assertNull($cached['updated_at']);
    }
}
