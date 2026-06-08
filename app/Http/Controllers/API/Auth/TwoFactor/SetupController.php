<?php

namespace App\Http\Controllers\API\Auth\TwoFactor;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticator;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

#[RequiresPlus]
class SetupController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthenticator $twoFactorAuth,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user): JsonResponse
    {
        return response()->json([
            'provisioning_uri' => $this->twoFactorAuth->setUp($user),
        ]);
    }
}
