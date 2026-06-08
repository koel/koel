<?php

namespace App\Http\Controllers\API\Auth\TwoFactor;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticator;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

#[RequiresPlus]
class SetupController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthenticator $twoFactorAuth,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user): JsonResponse
    {
        if ($user->hasTwoFactorEnabled()) {
            return response()->json([
                'message' => 'Two-factor authentication is already enabled. Disable it first to re-configure.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'provisioning_uri' => $this->twoFactorAuth->setUp($user),
        ]);
    }
}
