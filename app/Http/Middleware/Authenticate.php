<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;

/**
 * Only tokens with full abilities may pass. The audio-only token is limited to the `audio.auth` routes.
 */
class Authenticate extends BaseAuthenticate
{
    /** @inheritdoc */
    protected function authenticate($request, array $guards): void
    {
        parent::authenticate($request, $guards);

        if (!$request->user()?->tokenCan('*')) {
            $this->unauthenticated($request, $guards);
        }
    }
}
