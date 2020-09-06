<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\UserLoginRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\TokenManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Hashing\HashManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * @group 1. Authentication
 */
class AuthController extends Controller
{
    private $userRepository;
    private $hash;
    private $tokenManager;

    /** @var User|null */
    private $currentUser;

    public function __construct(
        UserRepository $userRepository,
        HashManager $hash,
        TokenManager $tokenManager,
        ?Authenticatable $currentUser
    )
    {
        $this->userRepository = $userRepository;
        $this->hash = $hash;
        $this->currentUser = $currentUser;
        $this->tokenManager = $tokenManager;
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
        /** @var User $user */
        $user = $this->userRepository->getFirstWhere('email', $request->email);

        if (!$user || !$this->hash->check($request->password, $user->password)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid credentials');
        }

        return response()->json([
            'token' => $this->tokenManager->createToken($user)->plainTextToken
        ]);
    }

    /**
     * Log the current user out
     *
     * @return JsonResponse
     */
    public function logout()
    {
        $this->tokenManager->destroyTokens($this->currentUser);

        return response()->json();
    }
}
