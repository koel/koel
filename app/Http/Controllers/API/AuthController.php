<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\UserLoginRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Log\Logger;
use Tymon\JWTAuth\JWTAuth;

class AuthController extends Controller
{
    private $auth;
    private $logger;

    public function __construct(JWTAuth $auth, Logger $logger)
    {
        $this->auth = $auth;
        $this->logger = $logger;
    }

    /**
     * Log a user in.
     *
     * @return JsonResponse
     */
    public function login(UserLoginRequest $request)
    {
        $token = $this->auth->attempt($request->only('email', 'password'));
        abort_unless($token, 401, 'Invalid credentials');

        return response()->json(compact('token'));
    }

    /**
     * Log the current user out.
     *
     * @return JsonResponse
     */
    public function logout()
    {
        if ($token = $this->auth->getToken()) {
            try {
                $this->auth->invalidate($token);
            } catch (Exception $e) {
                $this->logger->error($e);
            }
        }

        return response()->json();
    }
}
