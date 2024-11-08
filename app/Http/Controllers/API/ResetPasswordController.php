<?php

namespace App\Http\Controllers\API;

use App\Attributes\DisabledInDemo;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ResetPasswordRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\Response;

#[DisabledInDemo]
class ResetPasswordController extends Controller
{
    public function __invoke(ResetPasswordRequest $request, AuthenticationService $auth)
    {
        return $auth->tryResetPasswordUsingBroker($request->email, $request->password, $request->token)
            ? response()->noContent()
            : response('', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
