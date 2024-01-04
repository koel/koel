<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class OwnerNotSetPriorToScanException extends Exception
{
    private function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function create(): self
    {
        return new static('An owner must be set prior to scanning, as a song must be owned by a user.');
    }
}
