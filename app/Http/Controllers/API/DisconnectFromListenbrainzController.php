<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Integrations\ListenbrainzService;
use Illuminate\Contracts\Auth\Authenticatable;

class DisconnectFromListenbrainzController extends Controller
{
    /** @param User $user */
    public function __invoke(ListenbrainzService $listenbrainz, Authenticatable $user)
    {
        $listenbrainz->setUserToken($user, null);

        return response()->noContent();
    }
}
