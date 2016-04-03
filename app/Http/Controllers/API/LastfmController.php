<?php

namespace App\Http\Controllers\API;

use App\Services\Lastfm;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Tymon\JWTAuth\JWTAuth;

class LastfmController extends Controller
{
    /*
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Construct the controller and inject the current auth.
     *
     * @param Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Connect the current user to Last.fm.
     *
     * @param Redirector $redirector
     * @param Lastfm     $lastfm
     * @param JWTAuth    $auth
     *
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function connect(Redirector $redirector, Lastfm $lastfm, JWTAuth $auth = null)
    {
        abort_unless($lastfm->enabled(), 401, 'Koel is not configured to use with Last.fm yet.');

        $auth = $auth ?: $this->app['tymon.jwt.auth'];

        // A workaround to make sure Tymon's JWTAuth get the correct token via our custom
        // "jwt-token" query string instead of the default "token".
        // This is due to the problem that Last.fm returns the token via "token" as well.
        $auth->parseToken('', '', 'jwt-token');

        return $redirector->to(
            'https://www.last.fm/api/auth/?api_key='
            .$lastfm->getKey()
            .'&cb='.urlencode(route('lastfm.callback').'?jwt-token='.$auth->getToken())
        );
    }

    /**
     * Serve the callback request from Last.fm.
     *
     * @param Request $request
     * @param Lastfm  $lastfm
     *
     * @return \Illuminate\Http\Response
     */
    public function callback(Request $request, Lastfm $lastfm)
    {
        abort_unless($token = $request->input('token'), 500, 'Something wrong happened.');

        // Get the session key using the obtained token.
        abort_unless($sessionKey = $lastfm->getSessionKey($token), 500, 'Invalid token key.');

        $this->auth->user()->savePreference('lastfm_session_key', $sessionKey);

        return view('api.lastfm.callback');
    }

    /**
     * Set the Last.fm session key of the current user.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setSessionKey(Request $request)
    {
        $this->auth->user()->savePreference('lastfm_session_key', trim($request->input('key')));

        return response()->json();
    }

    /**
     * Disconnect the current user from Last.fm.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function disconnect()
    {
        return response()->json($this->auth->user()->deletePreference('lastfm_session_key'));
    }
}
