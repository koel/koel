<?php

namespace App\Http\Middleware;

use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\UserRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// @mago-ignore lint:cyclomatic-complexity
class SubsonicAuth
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $attempts = [
            fn (): User|Response|null => $this->authenticateViaApiKey($request),
            fn (): User|Response|null => $this->authenticateViaToken($request),
            fn (): User|Response|null => $this->authenticateViaPassword($request),
        ];

        foreach ($attempts as $attempt) {
            $result = $attempt();

            if ($result instanceof User) {
                Auth::setUser($result);

                return $next($request);
            }

            if ($result instanceof Response) {
                return $result;
            }
        }

        return self::missingParameter($request);
    }

    private function authenticateViaApiKey(Request $request): User|Response|null
    {
        $apiKey = self::stringInput($request, 'apiKey');

        if ($apiKey === '') {
            return null;
        }

        return $this->userRepository->findOneBySubsonicApiKey($apiKey) ?? self::wrongCredentials($request);
    }

    private function authenticateViaToken(Request $request): User|Response|null
    {
        $username = self::stringInput($request, 'u');
        $token = self::stringInput($request, 't');

        if ($username === '' || $token === '') {
            return null;
        }

        $salt = self::stringInput($request, 's');

        if ($salt === '') {
            return self::missingParameter($request);
        }

        $user = $this->userRepository->findOneByEmail($username);

        if (!$user) {
            return self::wrongCredentials($request);
        }

        $expected = md5($user->subsonic_api_key . $salt);

        return hash_equals($expected, strtolower($token)) ? $user : self::wrongCredentials($request);
    }

    private function authenticateViaPassword(Request $request): User|Response|null
    {
        $username = self::stringInput($request, 'u');
        $password = self::stringInput($request, 'p');

        if ($username === '' || $password === '') {
            return null;
        }

        $candidate = $password;

        if (str_starts_with($candidate, 'enc:')) {
            $hex = substr($candidate, 4);

            if ($hex === '' || (strlen($hex) % 2) !== 0 || !ctype_xdigit($hex)) {
                return self::wrongCredentials($request);
            }

            $candidate = hex2bin($hex);
        }

        $user = $this->userRepository->findOneByEmail($username);

        if (!$user) {
            return self::wrongCredentials($request);
        }

        return hash_equals($user->subsonic_api_key, $candidate) ? $user : self::wrongCredentials($request);
    }

    private static function stringInput(Request $request, string $key): string
    {
        $value = $request->input($key);

        return is_string($value) ? $value : '';
    }

    private static function missingParameter(Request $request): Response
    {
        return SubsonicResponse::error(10, 'Required parameter is missing.')->toResponse($request);
    }

    private static function wrongCredentials(Request $request): Response
    {
        return SubsonicResponse::error(40, 'Wrong username or password.')->toResponse($request);
    }
}
