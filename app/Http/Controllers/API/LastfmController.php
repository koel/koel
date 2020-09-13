<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\LastfmSetSessionKeyRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class LastfmController extends Controller
{
    /** @var User */
    private $currentUser;

    public function __construct(?Authenticatable $currentUser)
    {
        $this->currentUser = $currentUser;
    }

    public function setSessionKey(LastfmSetSessionKeyRequest $request)
    {
        $this->currentUser->savePreference('lastfm_session_key', trim($request->key));

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function disconnect()
    {
        $this->currentUser->deletePreference('lastfm_session_key');

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
