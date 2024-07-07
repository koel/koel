<?php

namespace App\Exceptions;

use Exception;

class MethodNotImplementedException extends Exception
{
    public static function method(string $method): self
    {
        return new self("Method $method is not implemented.");
    }
}
