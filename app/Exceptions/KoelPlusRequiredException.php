<?php

namespace App\Exceptions;

use DomainException;

class KoelPlusRequiredException extends DomainException
{
    public function __construct(string $message = 'This feature is only available in Koel Plus.')
    {
        parent::__construct($message);
    }
}
