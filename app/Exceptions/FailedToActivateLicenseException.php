<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class FailedToActivateLicenseException extends Exception
{
    public static function fromException(Throwable $e): self
    {
        return new static($e->getMessage(), $e->getCode(), $e);
    }
}
