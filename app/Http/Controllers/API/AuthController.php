<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\UserLoginRequest;
use App\Services\AuthenticationService;
use App\Values\CompositeToken;
use Closure;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class AuthController extends Controller
{
    use ThrottlesLogins;

    public function __construct(private readonly AuthenticationService $auth)
    {
    }

    public function login(UserLoginRequest $request)
    {
        $compositeToken = $this->throttleLoginRequest(
            fn () => $this->auth->login($request->email, $request->password),
            $request
        );

        return response()->json($compositeToken->toArray());
    }

    public function loginUsingOneTimeToken(Request $request)
    {
        $compositeToken = $this->throttleLoginRequest(
            fn () => $this->auth->loginViaOneTimeToken($request->input('token')),
            $request
        );

        return response()->json($compositeToken->toArray());
    }

    private function throttleLoginRequest(Closure $callback, Request $request): CompositeToken
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        try {
            return $callback();
        } catch (Throwable) {
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
