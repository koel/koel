<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\LastfmSetSessionKeyRequest;
use App\Models\User;
use App\Services\LastfmService;
use Illuminate\Contracts\Auth\Authenticatable;

class LastfmController extends Controller
{
    /** @param User $currentUser */
    public function __construct(private LastfmService $lastfm, private ?Authenticatable $currentUser)
    {
    }

    public function setSessionKey(LastfmSetSessionKeyRequest $request)
    {
        $this->lastfm->setUserSessionKey($this->currentUser, $request->key);

        return response()->noContent();
    }

    public function disconnect()
    {
        $this->lastfm->setUserSessionKey($this->currentUser, null);

        return response()->noContent();
    }
}
