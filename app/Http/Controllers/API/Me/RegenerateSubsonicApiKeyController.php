<?php

namespace App\Http\Controllers\API\Me;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Subsonic\AuthenticationService as SubsonicAuthenticationService;
use Illuminate\Contracts\Auth\Authenticatable;

class RegenerateSubsonicApiKeyController extends Controller
{
    public function __construct(
        private readonly SubsonicAuthenticationService $subsonicAuth,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        $this->subsonicAuth->assignApiKey($user);

        return UserResource::make($user->refresh());
    }
}
