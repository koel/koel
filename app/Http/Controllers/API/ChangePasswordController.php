<?php

namespace App\Http\Controllers\API;

use App\Attributes\DisabledInDemo;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ChangePasswordRequest;
use App\Models\User;
use App\Services\Auth\TokenManager;
use App\Services\UserService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ChangePasswordController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly Hasher $hash,
        private readonly UserService $userService,
        private readonly TokenManager $tokenManager,
        private readonly Authenticatable $user,
    ) {}

    #[DisabledInDemo(Response::HTTP_NO_CONTENT)]
    public function __invoke(ChangePasswordRequest $request)
    {
        abort_if(
            $this->user->sso_provider === 'Reverse Proxy',
            Response::HTTP_FORBIDDEN,
            'Password is managed by your SSO provider.',
        );

        throw_unless(
            $this->user->can_set_password_without_current_one
            || $this->hash->check($request->current_password, $this->user->password),
            ValidationException::withMessages(['current_password' => 'Invalid current password']),
        );

        $this->userService->changePassword($this->user, $request->new_password);

        $newApiToken = $this->tokenManager->refreshApiToken($request->bearerToken() ?: '')->plainTextToken;
        $this->tokenManager->destroyOtherSessions($this->user, $newApiToken);

        return response()->noContent()->header('Authorization', $newApiToken);
    }
}
