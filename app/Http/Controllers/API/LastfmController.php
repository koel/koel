<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\LastfmSetSessionKeyRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

/**
 * @group Last.fm integration
 */
class LastfmController extends Controller
{
    /** @var User */
    private $currentUser;

    public function __construct(?Authenticatable $currentUser)
    {
        $this->currentUser = $currentUser;
    }

    /**
     * Set Last.fm session key
     *
     * Set the Last.fm session key for the current user. This call should be made after the user is
     * [connected to Last.fm](https://www.last.fm/api/authentication).
     *
     * @bodyParam key string required The Last.fm [session key](https://www.last.fm/api/show/auth.getSession).
     * @response []
     *
     * @return JsonResponse
     */
    public function setSessionKey(LastfmSetSessionKeyRequest $request)
    {
        $this->currentUser->savePreference('lastfm_session_key', trim($request->key));

        return response()->json();
    }

    /**
     * Disconnect the current user from Last.fm
     *
     * @return JsonResponse
     */
    public function disconnect()
    {
        $this->currentUser->deletePreference('lastfm_session_key');

        return response()->json();
    }
}
