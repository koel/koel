<?php

namespace App\Http\Middleware;

use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\UserRepository;
use Closure;
use Illuminate\Http\Request;
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

        /** @var ?User $user */
        $user = $this->userRepository->findOneBy(['subsonic_api_key' => $apiKey]);

        if (!$user) {
            return SubsonicResponse::error(40, 'Wrong username or password.')->toResponse($request);
        }

        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }
}
