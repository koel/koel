<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LastfmService;
use Illuminate\Contracts\Auth\Authenticatable;

class DisconnectFromLastfmController extends Controller
{
    /** @param User $user */
    public function __invoke(LastfmService $lastfm, Authenticatable $user)
    {
        $lastfm->setUserSessionKey($user, null);

        return response()->noContent();
    }
}
