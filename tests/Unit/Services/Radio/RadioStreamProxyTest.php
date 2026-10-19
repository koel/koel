<?php

namespace Tests\Unit\Services\Radio;

use App\Models\RadioStation;
use App\Services\Radio\RadioStreamMetadata;
use App\Services\Radio\RadioStreamProxy;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\TrickleStream;
use Tests\TestCase;

class RadioStreamProxyTest extends TestCase
{
    #[Test]
    public function readTheWholeMetadataBlockWhenTheStreamDeliversItInPieces(): void
    {
        $station = RadioStation::factory()->createOne();
        $metadata = str_pad("StreamTitle='Daft Punk - Around the World';", 48, "\0");
        $stream = TrickleStream::open('audio' . chr(3) . $metadata . 'music');

        ob_start();
        (new RadioStreamProxy())->proxyWithMetadata($stream, $station, icyMetaInt: 5);
        $output = ob_get_clean();

        self::assertSame('audiomusic', $output);
        self::assertSame('Daft Punk - Around the World', RadioStreamMetadata::getCached($station)['stream_title']);
    }
}
