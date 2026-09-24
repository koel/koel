<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Integrations\LastfmService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class GetLastfmAuthorizationUrlController extends Controller
{
    /** @param User $user */
    public function __invoke(LastfmService $lastfm, Authenticatable $user)
    {
        abort_unless(
            LastfmService::enabled(),
            Response::HTTP_NOT_IMPLEMENTED,
            'Koel is not configured to use with Last.fm yet.',
        );

        return response()->json(['url' => $lastfm->getAuthorizationUrl($user)]);
    }
}
