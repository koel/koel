<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\UserLoginRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Log\Logger;
use Tymon\JWTAuth\JWTAuth;

/**
 * @group 1. Authentication
 */
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
     * Log a user in
     *
     * Koel uses [JSON Web Tokens](https://jwt.io/) (JWT) for authentication.
     * After the user has been authenticated, a random "token" will be returned.
     * This token should then be saved in a local storage and used as an `Authorization: Bearer` header
     * for consecutive calls.
     *
     * Notice: The token is valid for a week, after that the user will need to log in again.
     *
     * @bodyParam email string required The user's email. Example: john@doe.com
     * @bodyParam password string required The password. Example: SoSecureMuchW0w
     *
     * @response {
     *   "token": "<a-random-string>"
     * }
     * @reponse 401 {
     *   "message": "Invalid credentials"
     * }
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
     * Log the current user out
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
