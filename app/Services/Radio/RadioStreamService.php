<?php

namespace App\Services\Radio;

use App\Models\RadioStation;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RadioStreamService
{
    public function __construct(
        private readonly RadioStreamProxy $proxy,
    ) {}

    /**
     * Stream a radio station's audio while parsing ICY metadata.
     * The response strips ICY metadata blocks and sends clean audio to the client.
     */
    public function stream(RadioStation $station): StreamedResponse
    {
        return new StreamedResponse(function () use ($station) {
            $stream = $this->proxy->openStream($station->url);

            if (!$stream) {
                return;
            }

            $metaHeaders = stream_get_meta_data($stream);
            $icyMetaInt = $this->proxy->extractIcyMetaInt($metaHeaders['wrapper_data'] ?? []);

            if ($icyMetaInt === 0) {
                $this->proxy->proxyRawStream($stream);

                return;
            }

            $this->proxy->proxyWithMetadata($stream, $station, $icyMetaInt);
        }, headers: [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'no-cache, no-store',
            'Connection' => 'close',
        ]);
    }
}
