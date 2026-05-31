<?php

namespace App\Services\Subsonic\Contracts;

use App\Models\User;
use App\Values\Subsonic\SubsonicCredentials;

interface Authenticator
{
    public function attempt(SubsonicCredentials $credentials): ?User;
}
