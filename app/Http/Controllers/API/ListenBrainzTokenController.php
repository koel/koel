<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\SetListenBrainzTokenRequest;
use App\Models\User;
use App\Services\Integrations\ListenBrainzService;
use Illuminate\Contracts\Auth\Authenticatable;

class ListenBrainzTokenController extends Controller
{
    public function __construct(
        private readonly ListenBrainzService $listenbrainz,
    ) {}

    /** @param User $user */
    public function store(SetListenBrainzTokenRequest $request, Authenticatable $user)
    {
        $this->listenbrainz->setUserToken($user, $request->token);

        return response()->noContent();
    }

    /** @param User $user */
    public function destroy(Authenticatable $user)
    {
        $this->listenbrainz->setUserToken($user, null);

        return response()->noContent();
    }
}
