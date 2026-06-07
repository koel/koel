<?php

namespace App\Exceptions;

use App\Models\User;
use RuntimeException;

/**
 * Thrown by AuthenticationService::login when the credentials are valid but
 * the account has 2FA enabled. Caller is expected to issue a short-lived
 * login token and redirect the client to the two-factor challenge endpoint.
 */
class RequiresTwoFactorException extends RuntimeException
{
    public function __construct(
        public readonly User $user,
    ) {
        parent::__construct('Two-factor authentication is required.');
    }
}
