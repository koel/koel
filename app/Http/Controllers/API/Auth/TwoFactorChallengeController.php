<?php

namespace App\Http\Controllers\API\Auth;

use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InvalidLoginTokenException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Auth\TwoFactorChallengeRequest;
use App\Services\Auth\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $auth,
    ) {}

    public function __invoke(TwoFactorChallengeRequest $request): JsonResponse
    {
        try {
            $compositeToken = $this->auth->loginViaTwoFactorChallenge($request->login_token, $request->code);
        } catch (InvalidLoginTokenException|InvalidCredentialsException) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid credentials');
        }

        return response()->json($compositeToken->toArray());
    }
}
