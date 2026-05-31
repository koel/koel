<?php

namespace App\Services\Subsonic\Contracts;

use App\Models\User;
use App\Values\Subsonic\SubsonicCredentials;

interface Authenticator
{
    /**
     * Try to authenticate against this Subsonic auth scheme.
     *
     * Returns the authenticated User on success, null if this scheme doesn't
     * apply to the given credentials. Throws an exception if the scheme
     * applies but verification fails.
     */
    public function attempt(SubsonicCredentials $credentials): ?User;
}
