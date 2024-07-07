<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ForgotPasswordRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\Response;

class ForgotPasswordController extends Controller
{
    public function __invoke(ForgotPasswordRequest $request, AuthenticationService $auth)
    {
        static::disableInDemo();

        return $auth->trySendResetPasswordLink($request->email)
            ? response()->noContent()
            : response('', Response::HTTP_NOT_FOUND);
    }
}
