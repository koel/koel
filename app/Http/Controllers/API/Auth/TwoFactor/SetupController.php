<?php

namespace App\Http\Controllers\API\Auth\TwoFactor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticator;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class SetupController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthenticator $twoFactorAuth,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        abort_if($user->hasTwoFactorEnabled(), Response::HTTP_UNPROCESSABLE_ENTITY);

        return response()->json([
            'provisioning_uri' => $this->twoFactorAuth->setUp($user),
        ]);
    }
}
