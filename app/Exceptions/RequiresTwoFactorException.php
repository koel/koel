<?php

namespace App\Exceptions;

use RuntimeException;

class RequiresTwoFactorException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Two-factor authentication is required.');
    }
}
