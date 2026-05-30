<?php

namespace App\Exceptions\Subsonic;

use App\Exceptions\Contracts\SubsonicThrowable;
use RuntimeException;

abstract class SubsonicException extends RuntimeException implements SubsonicThrowable
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? static::defaultMessage());
    }

    abstract protected static function defaultMessage(): string;

    public function getSubsonicErrorMessage(): string
    {
        return $this->getMessage();
    }
}
