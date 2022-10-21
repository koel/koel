<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\UserLoginRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\TokenManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Hashing\HashManager;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class AuthController extends Controller
{
    use ThrottlesLogins;

    /** @param User $user */
    public function __construct(
        private UserRepository $userRepository,
        private HashManager $hash,
        private TokenManager $tokenManager,
        private ?Authenticatable $user
    ) {
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
        $this->user?->currentAccessToken()->delete(); // @phpstan-ignore-line

        return response()->noContent();
    }

    public function register(Request $request){
            $validated = $request->validate([
                'name' => ['required','min:3'],
                'email' => ['required','email', Rule::unique('users','email')],
                'password' => ['required','min:6', 'confirmed'],
                // 'password_confirmation' => ['required'],
            ]);

            $validated['password'] = bcrypt($validated['password']);

            $user = User::create($validated);

            //auth()->login($user);

            return response()->json([
                'token' => $this->tokenManager->createToken($user)->plainTextToken,
            ]);

        }

}
