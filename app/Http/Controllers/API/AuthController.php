<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\UserLoginRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\TokenManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Hashing\HashManager;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    use ThrottlesLogins;

    private UserRepository $userRepository;
    private HashManager $hash;
    private TokenManager $tokenManager;

    /** @var User */
    private ?Authenticatable $currentUser = null;

    public function __construct(
        UserRepository $userRepository,
        HashManager $hash,
        TokenManager $tokenManager,
        ?Authenticatable $currentUser
    ) {
        $this->userRepository = $userRepository;
        $this->hash = $hash;
        $this->currentUser = $currentUser;
        $this->tokenManager = $tokenManager;
    }

    public function login(UserLoginRequest $request)
    {
        /** @var User|null $user */
        $user = $this->userRepository->getFirstWhere('email', $request->email);

        if (!$user || !$this->hash->check($request->password, $user->password)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid credentials');
        }

        return response()->json([
            'token' => $this->tokenManager->createToken($user)->plainTextToken,
        ]);
    }

    public function logout()
    {
        $this->tokenManager->destroyTokens($this->currentUser);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
