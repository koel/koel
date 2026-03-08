<?php

namespace App\Services;

use App\Models\RadioStation;

class RadioStreamProxy
{
    /**
     * Open a stream to the radio station URL, requesting ICY metadata.
     * Falls back to a plain connection if ICY request fails.
     *
     * @return resource|false
     */
    public function openStream(string $url)
    {
        $context = stream_context_create([
            'http' => [
                'header' => "Icy-MetaData: 1\r\n",
                'timeout' => 5,
            ],
        ]);

        set_error_handler(static fn () => true);

        try {
            $stream = fopen($url, 'r', false, $context);

            if ($stream !== false) {
                return $stream;
            }

            return fopen($url, 'r');
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Extract the icy-metaint value from the response headers.
     */
    public function extractIcyMetaInt(array $headers): int
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
    public function proxyRawStream($stream): void
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
    public function proxyWithMetadata($stream, RadioStation $station, int $icyMetaInt): void
    {
        $bytesUntilMeta = $icyMetaInt;

        while (!feof($stream) && !connection_aborted()) {
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
                $this->processMetadataBlock($stream, $station);
                $bytesUntilMeta = $icyMetaInt;
            }
        }

        fclose($stream);
    }

    /**
     * Read and process a single ICY metadata block from the stream.
     *
     * @param resource $stream
     */
    private function processMetadataBlock($stream, RadioStation $station): void
    {
        $lengthByte = fread($stream, 1);

        if ($lengthByte === false || strlen($lengthByte) === 0) {
            return;
        }

        $metadataLength = ord($lengthByte) * 16;

        if ($metadataLength === 0) {
            return;
        }

        $metadataBlock = fread($stream, $metadataLength);

        if ($metadataBlock === false) {
            return;
        }

        $parsed = RadioStreamMetadata::parseIcyBlock($metadataBlock);

        if ($parsed['stream_title'] !== null) {
            RadioStreamMetadata::cache($station, $parsed['stream_title']);
        }
    }
}
