<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\LastfmSetSessionKeyRequest;
use App\Models\User;
use App\Services\LastfmService;
use Illuminate\Contracts\Auth\Authenticatable;

class LastfmController extends Controller
{
    private LastfmService $lastfm;

    /** @var User */
    private ?Authenticatable $currentUser;

    public function __construct(LastfmService $lastfm, ?Authenticatable $currentUser)
    {
        $this->lastfm = $lastfm;
        $this->currentUser = $currentUser;
    }

    public function setSessionKey(LastfmSetSessionKeyRequest $request)
    {
        $this->lastfm->setUserSessionKey($this->currentUser, trim($request->key));

        return response()->noContent();
    }

    public function disconnect()
    {
        $this->lastfm->setUserSessionKey($this->currentUser, null);

        return response()->noContent();
    }
}
