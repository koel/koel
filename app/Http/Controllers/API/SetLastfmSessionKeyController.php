<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\SetLastfmSessionKeyRequest;
use App\Models\User;
use App\Services\LastfmService;
use Illuminate\Contracts\Auth\Authenticatable;

class SetLastfmSessionKeyController extends Controller
{
    /** @param User $user */
    public function __invoke(SetLastfmSessionKeyRequest $request, LastfmService $lastfm, Authenticatable $user)
    {
        $lastfm->setUserSessionKey($user, $request->key);

        return response()->noContent();
    }
}
