<?php

namespace App\Http\Controllers\API;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

#[RequiresPlus]
class TwoFactorAuthController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthService $twoFactorAuth,
    ) {}

    public function setup(Authenticatable $user)
    {
        /** @var User $user */
        $secret = $this->twoFactorAuth->generateSecret();

        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        return response()->json([
            'provisioning_uri' => $this->twoFactorAuth->provisioningUri($user, $secret),
        ]);
    }

    public function confirm(Request $request, Authenticatable $user)
    {
        /** @var User $user */
        $code = (string) $request->input('code');

        if ($user->two_factor_secret === null || !$this->twoFactorAuth->verifyCode($user->two_factor_secret, $code)) {
            return response()->json(['message' => 'Invalid code.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cleartextCodes = $this->twoFactorAuth->generateRecoveryCodes();
        $user->two_factor_recovery_codes = $this->twoFactorAuth->hashRecoveryCodes($cleartextCodes);
        $user->two_factor_confirmed_at = now();
        $user->save();

        return response()->json(['recovery_codes' => $cleartextCodes]);
    }

    public function regenerateRecoveryCodes(Request $request, Authenticatable $user)
    {
        /** @var User $user */
        if (!$this->verifyChallengeCode($user, (string) $request->input('code'))) {
            return response()->json(['message' => 'Invalid code.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cleartextCodes = $this->twoFactorAuth->generateRecoveryCodes();
        $user->two_factor_recovery_codes = $this->twoFactorAuth->hashRecoveryCodes($cleartextCodes);
        $user->save();

        return response()->json(['recovery_codes' => $cleartextCodes]);
    }

    public function destroy(Request $request, Authenticatable $user)
    {
        /** @var User $user */
        if (!$this->verifyChallengeCode($user, (string) $request->input('code'))) {
            return response()->json(['message' => 'Invalid code.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return response()->noContent();
    }

    /**
     * Accept either a current TOTP code or a (one-time consumed) recovery code.
     * Used to gate sensitive 2FA management operations.
     */
    private function verifyChallengeCode(User $user, string $code): bool
    {
        if ($user->two_factor_secret !== null && $this->twoFactorAuth->verifyCode($user->two_factor_secret, $code)) {
            return true;
        }

        return $this->twoFactorAuth->consumeRecoveryCode($user, $code);
    }
}
