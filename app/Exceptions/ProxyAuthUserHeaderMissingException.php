<?php

namespace App\Exceptions;

class ProxyAuthUserHeaderMissingException extends ProxyAuthException
{
    public function __construct(
        public readonly string $expectedHeader,
        public readonly ?string $remoteAddr,
    ) {
        parent::__construct('User header not present on request');
    }

    /** @inheritDoc */
    public function getContext(): array
    {
        return [
            'expected_header' => $this->expectedHeader,
            'remote_addr' => $this->remoteAddr,
        ];
    }
}
