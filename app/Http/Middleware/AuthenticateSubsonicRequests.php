<?php

namespace App\Http\Middleware;

use App\Services\Subsonic\AuthenticationService;
use App\Values\Subsonic\SubsonicCredentials;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSubsonicRequests
{
    public function __construct(
        private readonly AuthenticationService $auth,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        Auth::setUser($this->auth->authenticate(SubsonicCredentials::make(
            apiKey: self::stringInput($request, 'apiKey'),
            username: self::stringInput($request, 'u'),
            token: self::stringInput($request, 't'),
            salt: self::stringInput($request, 's'),
            password: self::stringInput($request, 'p'),
        )));

        return $next($request);
    }

    private static function stringInput(Request $request, string $key): string
    {
        $value = $request->input($key);

        return is_string($value) ? $value : '';
    }
}
