<?php

namespace App\Http\Controllers\API\Auth\Passkey;

use App\Http\Controllers\Controller;
use App\Services\Auth\PasskeyService;

class LoginOptionsController extends Controller
{
    public function __construct(
        private readonly PasskeyService $passkeyService,
    ) {}

    public function __invoke()
    {
        return response()->json($this->passkeyService->generateLoginOptions());
    }
}
