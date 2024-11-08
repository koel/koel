<?php

namespace App\Http\Controllers\API;

use App\Attributes\DisabledInDemo;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ForgotPasswordRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\Response;

#[DisabledInDemo]
class ForgotPasswordController extends Controller
{
    public function __invoke(ForgotPasswordRequest $request, AuthenticationService $auth)
    {
        return $auth->trySendResetPasswordLink($request->email)
            ? response()->noContent()
            : response('', Response::HTTP_NOT_FOUND);
    }
}
