<?php

namespace App\Services;

use App\Models\RadioStation;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RadioStreamService
{
    /**
     * Parse an ICY metadata block from raw bytes.
     * The metadata format is: StreamTitle='Artist - Title';StreamUrl='...';
     *
     * @return array{stream_title: ?string}
     */
    public static function parseIcyMetadata(string $metadataBlock): array
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
    public static function cacheMetadata(RadioStation $station, string $streamTitle): void
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
    public static function getCachedMetadata(RadioStation $station): array
    {
        return Cache::get(self::cacheKey($station), ['stream_title' => null, 'updated_at' => null]);
    }

    public static function cacheKey(RadioStation $station): string
    {
        return "radio.metadata.{$station->id}";
    }

    /**
     * Stream a radio station's audio while parsing ICY metadata.
     * The response strips ICY metadata blocks and sends clean audio to the client.
     */
    public function stream(RadioStation $station): StreamedResponse
    {
        return new StreamedResponse(
            function () use ($station) {
                $context = stream_context_create([
                    'http' => [
                        'header' => "Icy-MetaData: 1\r\n",
                        'timeout' => 5,
                    ],
                ]);

                $stream = @fopen($station->url, 'r', false, $context);

                if (!$stream) {
                    // Fallback: redirect-style behavior by fetching without ICY
                    $fallback = @fopen($station->url, 'r');

                    if (!$fallback) {
                        return;
                    }

                    while (!feof($fallback) && !connection_aborted()) {
                        echo fread($fallback, 8192);
                        flush();
                    }

                    fclose($fallback);

                    return;
                }

                $metaHeaders = stream_get_meta_data($stream);
                $icyMetaInt = $this->extractIcyMetaInt($metaHeaders['wrapper_data'] ?? []);

                if ($icyMetaInt === 0) {
                    // Server doesn't support ICY metadata — just proxy the raw stream
                    $this->proxyRawStream($stream);

                    return;
                }

                $this->proxyWithMetadata($stream, $station, $icyMetaInt);
            },
            headers: [
                'Content-Type' => 'audio/mpeg',
                'Cache-Control' => 'no-cache, no-store',
                'Connection' => 'close',
            ],
        );
    }

    /**
     * Extract the icy-metaint value from the response headers.
     */
    private function extractIcyMetaInt(array $headers): int
    {
        foreach ($headers as $header) {
            if (stripos($header, 'icy-metaint:') === 0) {
                return (int) trim(substr($header, strlen('icy-metaint:')));
            }
        }

        return 0;
    }

    /**
     * Proxy the stream without ICY metadata parsing.
     *
     * @param resource $stream
     */
    private function proxyRawStream($stream): void
    {
        while (!feof($stream) && !connection_aborted()) {
            echo fread($stream, 8192);
            flush();
        }

        fclose($stream);
    }

    /**
     * Proxy the stream while extracting ICY metadata blocks.
     *
     * @param resource $stream
     */
    private function proxyWithMetadata($stream, RadioStation $station, int $icyMetaInt): void
    {
        $bytesUntilMeta = $icyMetaInt;

        while (!feof($stream) && !connection_aborted()) {
            // Read audio data up to the next metadata block
            $chunkSize = min($bytesUntilMeta, 8192);
            $data = fread($stream, $chunkSize);

            if ($data === false) {
                break;
            }

            $bytesRead = strlen($data);
            echo $data;
            flush();
            $bytesUntilMeta -= $bytesRead;

            if ($bytesUntilMeta === 0) {
                // Read the metadata length byte
                $lengthByte = fread($stream, 1);

                if ($lengthByte === false || strlen($lengthByte) === 0) {
                    break;
                }

                $metadataLength = ord($lengthByte) * 16;

                if ($metadataLength > 0) {
                    $metadataBlock = fread($stream, $metadataLength);

                    if ($metadataBlock !== false) {
                        $parsed = self::parseIcyMetadata($metadataBlock);

                        if ($parsed['stream_title'] !== null) {
                            self::cacheMetadata($station, $parsed['stream_title']);
                        }
                    }
                }

                // Reset counter for the next audio chunk
                $bytesUntilMeta = $icyMetaInt;
            }
        }

        fclose($stream);
    }
}
