<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AuthenticationService;
use Illuminate\Contracts\Auth\Authenticatable;

class GetOneTimeTokenController extends Controller
{
    /** @param User $user */
    public function __invoke(AuthenticationService $auth, Authenticatable $user)
    {
        return response()->json(['token' => $auth->generateOneTimeToken($user)]);
    }
}
