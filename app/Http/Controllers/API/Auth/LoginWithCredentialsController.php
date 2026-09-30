<?php

namespace App\Http\Controllers\API\Auth;

use App\Exceptions\InvalidCredentialsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\LoginWithCredentialsRequest;
use App\Services\Auth\AuthenticationService;
use Illuminate\Http\Response;

class LoginWithCredentialsController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $auth,
    ) {}

    public function __invoke(LoginWithCredentialsRequest $request)
    {
        try {
            $user = $this->auth->authenticate($request->email, $request->password);
        } catch (InvalidCredentialsException) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid credentials');
        }

        return response()->json($this->auth->logUserInOrStartTwoFactorChallenge($user));
    }
}
