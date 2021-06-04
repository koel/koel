<?php

namespace App\Http\Controllers;

use App\Http\Requests\API\LastfmCallbackRequest;
use App\Models\User;
use App\Services\LastfmService;
use App\Services\TokenManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class LastfmController extends Controller
{
    private LastfmService $lastfm;
    private TokenManager $tokenManager;

    /** @var User */
    private ?Authenticatable $currentUser;

    public function __construct(LastfmService $lastfm, TokenManager $tokenManager, ?Authenticatable $currentUser)
    {
        $this->lastfm = $lastfm;
        $this->tokenManager = $tokenManager;
        $this->currentUser = $currentUser;
    }

    public function connect()
    {
        abort_unless(
            $this->lastfm->enabled(),
            Response::HTTP_NOT_IMPLEMENTED,
            'Koel is not configured to use with Last.fm yet.'
        );

        $callbackUrl = urlencode(sprintf(
            '%s?api_token=%s',
            route('lastfm.callback'),
            // create a temporary token that can be deleted later
            $this->tokenManager->createToken($this->currentUser)->plainTextToken
        ));

        $url = sprintf('https://www.last.fm/api/auth/?api_key=%s&cb=%s', $this->lastfm->getKey(), $callbackUrl);

        return redirect($url);
    }

    public function callback(LastfmCallbackRequest $request)
    {
        $sessionKey = $this->lastfm->getSessionKey($request->token);
        abort_unless((bool) $sessionKey, Response::HTTP_INTERNAL_SERVER_ERROR, 'Invalid token key.');

        $this->lastfm->setUserSessionKey($this->currentUser, $sessionKey);

        // delete the tmp. token we created earlier
        $this->tokenManager->deleteTokenByPlainTextToken($request->api_token);

        return view('lastfm.callback');
    }
}
