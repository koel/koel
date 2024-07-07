<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ResetPasswordRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\Response;

class ResetPasswordController extends Controller
{
    public function __invoke(ResetPasswordRequest $request, AuthenticationService $auth)
    {
        static::disableInDemo();

        return $auth->tryResetPasswordUsingBroker($request->email, $request->password, $request->token)
            ? response()->noContent()
            : response('', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
