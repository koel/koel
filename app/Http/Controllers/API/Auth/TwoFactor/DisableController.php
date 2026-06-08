<?php

namespace App\Http\Controllers\API\Auth\TwoFactor;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Auth\TwoFactor\CodeBearingRequest;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticator;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

#[RequiresPlus]
class DisableController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthenticator $twoFactorAuth,
    ) {}

    /** @param User $user */
    public function __invoke(CodeBearingRequest $request, Authenticatable $user)
    {
        if (!$this->twoFactorAuth->verify($user, $request->code)) {
            return response()->json(['message' => 'Invalid code.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->twoFactorAuth->disable($user);

        return response()->noContent();
    }
}
