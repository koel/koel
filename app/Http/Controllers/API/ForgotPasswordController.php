<?php

namespace App\Http\Controllers\API;

use App\Attributes\DisabledInDemo;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ForgotPasswordRequest;
use App\Services\Auth\AuthenticationService;

#[DisabledInDemo]
class ForgotPasswordController extends Controller
{
    public function __invoke(ForgotPasswordRequest $request, AuthenticationService $auth)
    {
        $auth->trySendResetPasswordLink($request->email);

        return response()->noContent();
    }
}
