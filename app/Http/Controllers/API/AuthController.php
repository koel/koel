<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\UserLoginRequest;
use App\Models\User;
use Exception;
use JWTAuth;
use Log;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Log a user in.
     *
     * @param UserLoginRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(UserLoginRequest $request)
    {
        try {
            if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            Log:error($e);

            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json(compact('token'));
    }

    /**
     * Log the current user out.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        if ($token = JWTAuth::getToken()) {
            try {
                JWTAuth::invalidate($token);
            } catch (Exception $e) {
                Log::error($e);
            }
        }

        return response()->json();
    }
}
