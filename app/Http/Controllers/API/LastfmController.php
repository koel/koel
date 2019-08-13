<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\LastfmCallbackRequest;
use App\Http\Requests\API\LastfmSetSessionKeyRequest;
use App\Services\LastfmService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTAuth;

/**
 * @group Last.fm integration
 */
class LastfmController extends Controller
{
    protected $auth;
    private $lastfmService;
    private $jwtAuth;

    public function __construct(Guard $auth, LastfmService $lastfmService, JWTAuth $jwtAuth)
    {
        $this->auth = $auth;
        $this->lastfmService = $lastfmService;
        $this->jwtAuth = $jwtAuth;
    }

    /**
     * Connect to Last.fm.
     *
     * [Connect](https://www.last.fm/api/authentication) the current user to Last.fm.
     * This is actually NOT an API request. The application should instead redirect the current user to this route,
     * which will send them to Last.fm for authentication. After authentication is successful, the user will be
     * redirected back to `api/lastfm/callback?token=<Last.fm token>`.
     *
     * @queryParam jwt-token required The JWT token of the user.
     *
     * @throws JWTException
     *
     * @return RedirectResponse
     */
    public function connect()
    {
        abort_unless($this->lastfmService->enabled(), 401, 'Koel is not configured to use with Last.fm yet.');

        // A workaround to make sure Tymon's JWTAuth get the correct token via our custom
        // "jwt-token" query string instead of the default "token".
        // This is due to the problem that Last.fm returns the token via "token" as well.
        $this->jwtAuth->parseToken('', '', 'jwt-token');

        $callbackUrl = urlencode(sprintf('%s?jwt-token=%s', route('lastfm.callback'), $this->jwtAuth->getToken()));
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

        $this->auth->user()->savePreference('lastfm_session_key', $sessionKey);

        return view('api.lastfm.callback');
    }

    /**
     * Set Last.fm session key.
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
        $this->auth->user()->savePreference('lastfm_session_key', trim($request->key));

        return response()->json();
    }

    /**
     * Disconnect the current user from Last.fm.
     *
     * @return JsonResponse
     */
    public function disconnect()
    {
        $this->auth->user()->deletePreference('lastfm_session_key');

        return response()->json();
    }
}
