<?php

namespace App\Exceptions;

class ProxyAuthIpNotAllowedException extends ProxyAuthException
{
    /** @param array<int, string> $allowList */
    public function __construct(
        public readonly ?string $remoteAddr,
        public readonly array $allowList,
    ) {
        parent::__construct('Remote address not in allow list');
    }

    /** @inheritDoc */
    public function getContext(): array
    {
        return [
            'remote_addr' => $this->remoteAddr,
            'allow_list' => $this->allowList,
        ];
    }
}
