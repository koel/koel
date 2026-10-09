<?php

namespace App\Http\Controllers\API\Auth\Passkey;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Auth\Passkey\PasskeyRegistrationOptionsRequest;
use App\Models\User;
use App\Services\Auth\PasskeyService;
use Illuminate\Contracts\Auth\Authenticatable;

class RegistrationOptionsController extends Controller
{
    public function __construct(
        private readonly PasskeyService $passkeyService,
    ) {}

    /** @param User $user */
    public function __invoke(PasskeyRegistrationOptionsRequest $request, Authenticatable $user)
    {
        if ($request->has('credential')) {
            $this->passkeyService->confirmIdentityWithPasskey($user, $request->credential());
        } else {
            $this->passkeyService->confirmIdentityWithPassword($user, $request->password, $request->code);
        }

        return response()->json($this->passkeyService->generateRegistrationOptions($user));
    }
}
