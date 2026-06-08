<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $auth,
    ) {}

    public function __invoke(Request $request): Response
    {
        rescue(fn () => $this->auth->logoutViaBearerToken($request->bearerToken()));

        return response()->noContent();
    }
}
