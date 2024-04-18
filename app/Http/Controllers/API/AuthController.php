<?php

namespace App\Http\Controllers\API;

use App\Exceptions\InvalidCredentialsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\UserLoginRequest;
use App\Services\AuthenticationService;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    use ThrottlesLogins;

    public function __construct(private readonly AuthenticationService $auth)
    {
    }

    public function login(UserLoginRequest $request)
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        try {
            return response()->json($this->auth->login($request->email, $request->password)->toArray());
        } catch (InvalidCredentialsException) {
            $this->incrementLoginAttempts($request);
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid credentials');
        }
    }

    public function logout(Request $request): Response
    {
        attempt(fn () => $this->auth->logoutViaBearerToken($request->bearerToken()));

        return response()->noContent();
    }

    /**
     * For the throttle middleware.
     */
    protected function username(): string
    {
        return 'email';
    }
}
