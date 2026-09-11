<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\SetListenbrainzTokenRequest;
use App\Models\User;
use App\Services\Integrations\ListenbrainzService;
use Illuminate\Contracts\Auth\Authenticatable;

class SetListenbrainzTokenController extends Controller
{
    /** @param User $user */
    public function __invoke(
        SetListenbrainzTokenRequest $request,
        ListenbrainzService $listenbrainz,
        Authenticatable $user,
    ) {
        $listenbrainz->setUserToken($user, $request->token);

        return response()->noContent();
    }
}
