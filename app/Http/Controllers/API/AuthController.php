<?php

namespace App\Http\Controllers\API;

use App\Exceptions\RequiresTwoFactorException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\UserLoginRequest;
use App\Services\Auth\AuthenticationService;
use App\Values\CompositeToken;
use Closure;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class AuthController extends Controller
{
    use ThrottlesLogins;

    public function __construct(
        private readonly AuthenticationService $auth,
    ) {}

    public function login(UserLoginRequest $request): JsonResponse
    {
        try {
            $compositeToken = $this->throttleLoginRequest(fn () => $this->auth->login(
                $request->email,
                $request->password,
            ), $request);
        } catch (RequiresTwoFactorException $e) {
            return response()->json([
                'requires_two_factor' => true,
                'login_token' => $this->auth->generateTwoFactorLoginToken($e->user),
                'message' =>
                    'This account has two-factor authentication enabled. '
                        . 'If you are signing in from an older mobile player that cannot prompt for a code, '
                        . 'use the QR code on your profile page in the web app instead.',
            ], Response::HTTP_OK);
        }

        return response()->json($compositeToken->toArray());
    }

    public function twoFactorChallenge(Request $request): JsonResponse
    {
        $compositeToken = $this->throttleLoginRequest(fn () => $this->auth->loginViaTwoFactorChallenge(
            (string) $request->input('login_token'),
            (string) $request->input('code'),
        ), $request);

        return response()->json($compositeToken->toArray());
    }

    public function loginUsingOneTimeToken(Request $request): JsonResponse
    {
        $compositeToken = $this->throttleLoginRequest(fn () => $this->auth->loginViaOneTimeToken($request->input(
            'token',
        )), $request);

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
        } catch (RequiresTwoFactorException $e) {
            throw $e;
        } catch (Throwable) {
            $this->incrementLoginAttempts($request);
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid credentials');
        }
    }

    public function logout(Request $request): Response
    {
        rescue(fn () => $this->auth->logoutViaBearerToken($request->bearerToken()));

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
