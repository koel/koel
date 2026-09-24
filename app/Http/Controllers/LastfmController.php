<?php

namespace App\Http\Controllers;

use App\Http\Requests\API\LastfmCallbackRequest;
use App\Services\Integrations\LastfmService;
use Illuminate\Http\Response;

class LastfmController extends Controller
{
    public function __construct(
        private readonly LastfmService $lastfm,
    ) {}

    public function callback(LastfmCallbackRequest $request)
    {
        $user = $this->lastfm->pullUserFromConnectState($request->state);
        abort_unless((bool) $user, Response::HTTP_FORBIDDEN, 'Invalid or expired state.');

        $sessionKey = $this->lastfm->getSessionKey($request->token);
        abort_unless((bool) $sessionKey, Response::HTTP_INTERNAL_SERVER_ERROR, 'Invalid token key.');

        $this->lastfm->setUserSessionKey($user, $sessionKey);

        return view('lastfm.callback');
    }
}
