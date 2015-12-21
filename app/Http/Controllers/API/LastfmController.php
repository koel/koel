<?php

namespace App\Http\Controllers\API;

use App\Services\Lastfm;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

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
     * @param Guard      $auth
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
     *
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function connect(Redirector $redirector, Lastfm $lastfm)
    {
        if (!$lastfm->enabled()) {
            abort(401, 'Koel is not configured to use with Last.fm yet.');
        }

        return $redirector->to(
            'https://www.last.fm/api/auth/?api_key='
            .$lastfm->getKey()
            .'&cb='.route('lastfm.callback')
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
        if (!$token = $request->input('token')) {
            abort(500, 'Something wrong happened.');
        }

        // Get the session key using the obtained token.
        if (!$sessionKey = $lastfm->getSessionKey($token)) {
            abort(500, 'Invalid token key.');
        }

        $this->auth->user()->savePreference('lastfm_session_key', $sessionKey);

        return view('api.lastfm.callback');
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
