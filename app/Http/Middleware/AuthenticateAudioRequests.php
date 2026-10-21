<?php

namespace App\Http\Middleware;

use App\Repositories\UserRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticateAudioRequests
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        if ($request->hasValidRelativeSignatureWhileIgnoring(['time'])) {
            $user = $this->userRepository->getOneByPublicId($request->string('user')->toString());
            abort_if($user->isPendingDeletion(), Response::HTTP_UNAUTHORIZED);

            Auth::setUser($user);

            return $next($request);
        }

        abort_unless($request->user()?->tokenCan('audio'), Response::HTTP_UNAUTHORIZED);

        return $next($request);
    }
}
