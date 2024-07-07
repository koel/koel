<?php

namespace App\Exceptions;

use Exception;

class KoelPlusRequiredException extends Exception
{
    public function __construct(string $message = 'This feature is only available in Koel Plus.')
    {
        parent::__construct($message);
    }
}
