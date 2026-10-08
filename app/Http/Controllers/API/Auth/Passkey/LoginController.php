<?php

namespace App\Http\Controllers\API\Auth\Passkey;

use App\Exceptions\InvalidLoginTokenException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Auth\Passkey\PasskeyLoginRequest;
use App\Services\Auth\AuthenticationService;
use App\Services\Auth\PasskeyService;
use Illuminate\Http\Response;

class LoginController extends Controller
{
    public function __construct(
        private readonly PasskeyService $passkeyService,
        private readonly AuthenticationService $auth,
    ) {}

    public function __invoke(PasskeyLoginRequest $request)
    {
        try {
            $user = $this->passkeyService->verifyLogin($request->login_token, $request->credential());
        } catch (InvalidLoginTokenException) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid credentials');
        }

        return response()->json($this->auth->logUserIn($user)->toArray());
    }
}
