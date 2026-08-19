<?php

namespace Tests\Unit\Services\Streamer;

use App\Services\Streamer\RangeContentLengthOutputWriter;
use DaveRandom\Resume\FileResource;
use DaveRandom\Resume\OutputWriter;
use DaveRandom\Resume\RangeSet;
use DaveRandom\Resume\ResourceServlet;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RangeContentLengthOutputWriterTest extends TestCase
{
    #[Test]
    public function reportsTheCombinedLengthOfTheNormalizedRanges(): void
    {
        $path = public_path('sandbox/media/range-test.txt');
        File::put($path, '0123456789');

        $resource = new FileResource($path, 'text/plain');
        $rangeSet = RangeSet::createFromHeader('bytes=2-4,8-');
        self::assertNotNull($rangeSet);

        $delegate = new class implements OutputWriter {
            public int $responseCode = 0;

            /** @var array<string, string> */
            public array $headers = [];

            public string $data = '';

            public function setResponseCode(int $code): void
            {
                $this->responseCode = $code;
            }

            public function sendHeader(string $name, string $value): void
            {
                $this->headers[strtolower($name)] = $value;
            }

            public function sendData(string $data): void
            {
                $this->data .= $data;
            }
        };
        $outputWriter = new RangeContentLengthOutputWriter(
            $delegate,
            $rangeSet->getRangesForSize($resource->getLength()),
        );

        (new ResourceServlet($resource))->sendResource($rangeSet, $outputWriter);

        self::assertSame(206, $delegate->responseCode);
        self::assertSame('5', $delegate->headers['content-length']);
        self::assertSame('bytes 2-4,8-9/10', $delegate->headers['content-range']);
        self::assertSame('23489', $delegate->data);
    }
}
