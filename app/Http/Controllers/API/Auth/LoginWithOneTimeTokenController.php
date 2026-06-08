<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class LoginWithOneTimeTokenController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $auth,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $compositeToken = $this->auth->loginViaOneTimeToken((string) $request->input('token'));
        } catch (Throwable) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid credentials');
        }

        return response()->json($compositeToken->toArray());
    }
}
