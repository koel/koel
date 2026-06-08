<?php

namespace App\Http\Controllers\API\Auth;

use App\Exceptions\RequiresTwoFactorException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\LoginWithCredentialsRequest;
use App\Services\Auth\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

class LoginWithCredentialsController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $auth,
    ) {}

    public function __invoke(LoginWithCredentialsRequest $request): JsonResponse
    {
        try {
            $compositeToken = $this->auth->login($request->email, $request->password);
        } catch (RequiresTwoFactorException $e) {
            return response()->json([
                'two_factor' => true,
                'login_token' => $this->auth->generateTwoFactorLoginToken($e->user),
            ]);
        } catch (Throwable) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid credentials');
        }

        return response()->json($compositeToken->toArray());
    }
}
