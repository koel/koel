<?php

namespace App\Http\Controllers\API\Auth;

use App\Exceptions\InvalidLoginTokenException;
use App\Http\Controllers\Controller;
use App\Services\Auth\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LoginWithOneTimeTokenController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $auth,
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $compositeToken = $this->auth->loginViaOneTimeToken((string) $request->input('token'));
        } catch (InvalidLoginTokenException) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid credentials');
        }

        return response()->json($compositeToken->toArray());
    }
}
