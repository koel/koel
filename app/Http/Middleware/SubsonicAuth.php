<?php

namespace App\Http\Middleware;

use App\Exceptions\Subsonic\InvalidCredentialsException;
use App\Exceptions\Subsonic\RequiredParameterMissingException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Subsonic\AuthenticationService as SubsonicAuthenticationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SubsonicAuth
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SubsonicAuthenticationService $subsonicAuth,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        Auth::setUser($this->authenticate($request));

        return $next($request);
    }

    private function authenticate(Request $request): User
    {
        $attempts = [
            fn (): ?User => $this->authenticateViaApiKey($request),
            fn (): ?User => $this->authenticateViaToken($request),
            fn (): ?User => $this->authenticateViaPassword($request),
        ];

        foreach ($attempts as $attempt) {
            $user = $attempt();

            if ($user) {
                return $user;
            }
        }

        throw new RequiredParameterMissingException();
    }

    private function authenticateViaApiKey(Request $request): ?User
    {
        $apiKey = self::stringInput($request, 'apiKey');

        if ($apiKey === '') {
            return null;
        }

        $user = $this->userRepository->findOneBySubsonicApiKeyHash($this->subsonicAuth->hash($apiKey));
        throw_unless($user, InvalidCredentialsException::class);

        return $user;
    }

    private function authenticateViaToken(Request $request): ?User
    {
        $username = self::stringInput($request, 'u');
        $token = self::stringInput($request, 't');

        if ($username === '' || $token === '') {
            return null;
        }

        $salt = self::stringInput($request, 's');
        throw_if($salt === '', RequiredParameterMissingException::class);

        $user = $this->userRepository->findOneByEmail($username);
        throw_unless($user, InvalidCredentialsException::class);

        $expected = md5($user->subsonic_api_key . $salt);
        throw_unless(hash_equals($expected, strtolower($token)), InvalidCredentialsException::class);

        return $user;
    }

    private function authenticateViaPassword(Request $request): ?User
    {
        $username = self::stringInput($request, 'u');
        $password = self::stringInput($request, 'p');

        if ($username === '' || $password === '') {
            return null;
        }

        $candidate = $password;

        if (str_starts_with($candidate, 'enc:')) {
            $hex = substr($candidate, 4);
            throw_if(
                $hex === '' || (strlen($hex) % 2) !== 0 || !ctype_xdigit($hex),
                InvalidCredentialsException::class,
            );

            $candidate = hex2bin($hex);
        }

        $user = $this->userRepository->findOneByEmail($username);
        throw_unless($user, InvalidCredentialsException::class);
        throw_unless(hash_equals($user->subsonic_api_key, $candidate), InvalidCredentialsException::class);

        return $user;
    }

    private static function stringInput(Request $request, string $key): string
    {
        $value = $request->input($key);

        return is_string($value) ? $value : '';
    }
}
