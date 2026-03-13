<?php

namespace Tests\Unit\Services;

use App\Models\RadioStation;
use App\Services\RadioStreamMetadata;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RadioStreamServiceTest extends TestCase
{
    #[Test]
    public function parseIcyMetadataExtractsStreamTitle(): void
    {
        $metadata = "StreamTitle='Artist - Song Title';StreamUrl='';";
        $result = RadioStreamMetadata::parseIcyBlock($metadata);

        self::assertSame('Artist - Song Title', $result['stream_title']);
    }

    #[Test]
    public function parseIcyMetadataReturnsNullForEmptyTitle(): void
    {
        $metadata = "StreamTitle='';StreamUrl='';";
        $result = RadioStreamMetadata::parseIcyBlock($metadata);

        self::assertNull($result['stream_title']);
    }

    #[Test]
    public function parseIcyMetadataReturnsNullForNoMatch(): void
    {
        $metadata = 'no metadata here';
        $result = RadioStreamMetadata::parseIcyBlock($metadata);

        self::assertNull($result['stream_title']);
    }

    #[Test]
    public function cacheAndGetMetadata(): void
    {
        $station = RadioStation::factory()->createOne();

        RadioStreamMetadata::cache($station, 'Now Playing - Track');
        $cached = RadioStreamMetadata::getCached($station);

        self::assertSame('Now Playing - Track', $cached['stream_title']);
        self::assertNotNull($cached['updated_at']);
    }

    #[Test]
    public function getCachedMetadataReturnsDefaultWhenEmpty(): void
    {
        $station = RadioStation::factory()->createOne();

        Cache::forget(RadioStreamMetadata::cacheKey($station));
        $cached = RadioStreamMetadata::getCached($station);

        self::assertNull($cached['stream_title']);
        self::assertNull($cached['updated_at']);
    }
}
