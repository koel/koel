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
    private $lastfmService;
    private $tokenManager;

    /** @var User */
    private $currentUser;

    public function __construct(LastfmService $lastfmService, TokenManager $tokenManager, ?Authenticatable $currentUser)
    {
        $this->lastfmService = $lastfmService;
        $this->tokenManager = $tokenManager;
        $this->currentUser = $currentUser;
    }

    public function connect()
    {
        abort_unless(
            $this->lastfmService->enabled(),
            Response::HTTP_NOT_IMPLEMENTED,
            'Koel is not configured to use with Last.fm yet.'
        );

        $callbackUrl = urlencode(sprintf(
            '%s?api_token=%s',
            route('lastfm.callback'),
            // create a temporary token that can be deleted later
            $this->tokenManager->createToken($this->currentUser)->plainTextToken
        ));

        $url = sprintf('https://www.last.fm/api/auth/?api_key=%s&cb=%s', $this->lastfmService->getKey(), $callbackUrl);

        return redirect($url);
    }

    public function callback(LastfmCallbackRequest $request)
    {
        $sessionKey = $this->lastfmService->getSessionKey($request->token);
        abort_unless($sessionKey, Response::HTTP_INTERNAL_SERVER_ERROR, 'Invalid token key.');

        $this->currentUser->savePreference('lastfm_session_key', $sessionKey);

        // delete the tmp. token we created earlier
        $this->tokenManager->deleteTokenByPlainTextToken($request->api_token);

        return view('lastfm.callback');
    }
}
