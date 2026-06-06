<?php

namespace App\Services\Radio;

use App\Models\RadioStation;
use Illuminate\Support\Facades\Cache;

class RadioStreamMetadata
{
    /**
     * Parse an ICY metadata block from raw bytes.
     * The metadata format is: StreamTitle='Artist - Title';StreamUrl='...';
     *
     * @return array{stream_title: ?string}
     */
    public static function parseIcyBlock(string $metadataBlock): array
    {
        $result = ['stream_title' => null];

        if (preg_match("/StreamTitle='(.*?)';/", $metadataBlock, $matches)) {
            $title = trim($matches[1]);

            if ($title !== '') {
                $result['stream_title'] = $title;
            }
        }

        return $result;
    }

    /**
     * Cache the latest ICY metadata for a radio station.
     */
    public static function cache(RadioStation $station, string $streamTitle): void
    {
        Cache::put(
            self::cacheKey($station),
            ['stream_title' => $streamTitle, 'updated_at' => now()->toISOString()],
            now()->addMinutes(10),
        );
    }

    /**
     * Get the cached metadata for a radio station.
     *
     * @return array{stream_title: ?string, updated_at: ?string}
     */
    public static function getCached(RadioStation $station): array
    {
        return Cache::get(self::cacheKey($station), ['stream_title' => null, 'updated_at' => null]);
    }

    public static function cacheKey(RadioStation $station): string
    {
        return "radio.metadata.{$station->id}";
    }
}
