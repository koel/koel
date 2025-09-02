<?php

namespace App\Http\Controllers\API;

use App\Attributes\DisabledInDemo;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ResetPasswordRequest;
use App\Services\AuthenticationService;
use Illuminate\Validation\ValidationException;

#[DisabledInDemo]
class ResetPasswordController extends Controller
{
    public function __invoke(ResetPasswordRequest $request, AuthenticationService $auth)
    {
        if ($auth->tryResetPasswordUsingBroker($request->email, $request->password, $request->token)) {
            return response()->noContent();
        }

        throw ValidationException::withMessages(['token' => 'Invalid or expired token.']);
    }
}
