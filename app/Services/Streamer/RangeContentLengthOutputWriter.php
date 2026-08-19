<?php

namespace App\Services\Streamer;

use DaveRandom\Resume\OutputWriter;
use DaveRandom\Resume\Range;

final readonly class RangeContentLengthOutputWriter implements OutputWriter
{
    private int $contentLength;

    /** @param array<Range> $ranges */
    public function __construct(
        private OutputWriter $delegate,
        array $ranges,
    ) {
        $this->contentLength = array_sum(array_map(static fn (Range $range): int => $range->getLength(), $ranges));
    }

    public function setResponseCode(int $code): void
    {
        $this->delegate->setResponseCode($code);
    }

    public function sendHeader(string $name, string $value): void
    {
        if (strcasecmp($name, 'content-length') === 0) {
            $value = (string) $this->contentLength;
        }

        $this->delegate->sendHeader($name, $value);
    }

    public function sendData(string $data): void
    {
        $this->delegate->sendData($data);
    }
}
