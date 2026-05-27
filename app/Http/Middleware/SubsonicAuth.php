<?php

namespace App\Http\Middleware;

use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\UserRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SubsonicAuth
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->input('apiKey');

        if (!$apiKey) {
            return SubsonicResponse::error(10, 'Required parameter is missing.')->toResponse($request);
        }

        $user = $this->userRepository->findOneBySubsonicApiKey($apiKey);

        if (!$user) {
            return SubsonicResponse::error(40, 'Wrong username or password.')->toResponse($request);
        }

        Auth::setUser($user);

        return $next($request);
    }
}
