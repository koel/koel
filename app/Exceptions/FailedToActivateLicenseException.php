<?php

namespace App\Exceptions;

use Exception;
use Saloon\Exceptions\Request\RequestException;
use Throwable;

final class FailedToActivateLicenseException extends Exception
{
    public static function fromThrowable(Throwable $e): self
    {
        return new self($e->getMessage(), $e->getCode(), $e);
    }

    public static function fromRequestException(RequestException $e): self
    {
        try {
            return new self(object_get($e->getResponse()->object(), 'error'), $e->getStatus());
        } catch (Throwable) {
            return self::fromThrowable($e);
        }
    }
}
