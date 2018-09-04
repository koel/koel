<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\LastfmCallbackRequest;
use App\Http\Requests\API\LastfmSetSessionKeyRequest;
use App\Services\LastfmService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTAuth;

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
     * Connect the current user to Last.fm.
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
     *
     * @return Response
     */
    public function callback(LastfmCallbackRequest $request)
    {
        $sessionKey = $this->lastfmService->getSessionKey($request->token);

        abort_unless($sessionKey, 500, 'Invalid token key.');

        $this->auth->user()->savePreference('lastfm_session_key', $sessionKey);

        return view('api.lastfm.callback');
    }

    /**
     * Set the Last.fm session key of the current user.
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
        return response()->json($this->auth->user()->deletePreference('lastfm_session_key'));
    }
}
