<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\LastfmCallbackRequest;
use App\Http\Requests\API\LastfmSetSessionKeyRequest;
use App\Models\User;
use App\Services\LastfmService;
use App\Services\TokenManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * @group Last.fm integration
 */
class LastfmController extends Controller
{
    private $lastfmService;
    private $tokenManager;

    /** @var User */
    private $currentUser;

    public function __construct(LastfmService $lastfmService, TokenManager $tokenManager, Authenticatable $currentUser)
    {
        $this->lastfmService = $lastfmService;
        $this->tokenManager = $tokenManager;
        $this->currentUser = $currentUser;
    }

    /**
     * Connect to Last.fm
     *
     * [Connect](https://www.last.fm/api/authentication) the current user to Last.fm.
     * This is actually NOT an API request. The application should instead redirect the current user to this route,
     * which will send them to Last.fm for authentication. After authentication is successful, the user will be
     * redirected back to `api/lastfm/callback?token=<Last.fm token>`.
     *
     * @queryParam jwt-token required The JWT token of the user. (Deprecated. Use api_token instead).
     * @queryParam api_token required Authentication token of the current user.
     * @response []
     *
     * @return RedirectResponse
     */
    public function connect()
    {
        abort_unless($this->lastfmService->enabled(), 401, 'Koel is not configured to use with Last.fm yet.');

        $callbackUrl = urlencode(sprintf(
            '%s?api_token=%s',
            route('lastfm.callback'),
            request('api_token')
        ));

        $url = sprintf('https://www.last.fm/api/auth/?api_key=%s&cb=%s', $this->lastfmService->getKey(), $callbackUrl);

        return redirect($url);
    }

    /**
     * Serve the callback request from Last.fm.
     */
    public function callback(LastfmCallbackRequest $request)
    {
        $sessionKey = $this->lastfmService->getSessionKey($request->token);

        abort_unless($sessionKey, 500, 'Invalid token key.');

        $this->currentUser->savePreference('lastfm_session_key', $sessionKey);

        return view('api.lastfm.callback');
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
     * @param LastfmSetSessionKeyRequest $request
     *
     * @return JsonResponse
     */
    public function setSessionKey(LastfmSetSessionKeyRequest $request)
    {
        $this->currentUser->savePreference('lastfm_session_key', trim($request->key));

        return response()->json();
    }

    /**
     * Disconnect the current user from Last.fm.
     *
     * @return JsonResponse
     */
    public function disconnect()
    {
        $this->currentUser->deletePreference('lastfm_session_key');

        return response()->json();
    }
}
