<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AuthenticationService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class LogOutOtherSessionsController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly AuthenticationService $auth,
        private readonly Authenticatable $user,
    ) {}

    public function __invoke(Request $request)
    {
        $this->auth->logOutOtherSessions($this->user, $request->bearerToken());

        return response()->noContent();
    }
}
