<?php

namespace App\Http\Controllers\API\Auth\Passkey;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\PasskeyService;
use Illuminate\Contracts\Auth\Authenticatable;

class ConfirmationOptionsController extends Controller
{
    public function __construct(
        private readonly PasskeyService $passkeyService,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        return response()->json($this->passkeyService->generateConfirmationOptions($user));
    }
}
