<?php

namespace App\Exceptions;

use RuntimeException;

abstract class ProxyAuthException extends RuntimeException
{
    /** @return array<string, mixed> */
    abstract public function getContext(): array;
}
