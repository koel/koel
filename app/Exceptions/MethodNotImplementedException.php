<?php

namespace App\Exceptions;

use BadMethodCallException;

class MethodNotImplementedException extends BadMethodCallException
{
    public static function method(string $method): self
    {
        return new self("Method $method is not implemented.");
    }
}
