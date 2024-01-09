<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Values\CompositeToken;
use Illuminate\Hashing\HashManager;

class AuthenticationService
{
    public function __construct(
        private UserRepository $userRepository,
        private TokenManager $tokenManager,
        private HashManager $hash
    ) {
    }

    public function login(string $email, string $password): CompositeToken
    {
        /** @var User|null $user */
        $user = $this->userRepository->getFirstWhere('email', $email);

        if (!$user || !$this->hash->check($password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        if ($this->hash->needsRehash($user->password)) {
            $user->password = $this->hash->make($password);
            $user->save();
        }

        return $this->tokenManager->createCompositeToken($user);
    }

    public function logoutViaBearerToken(string $token): void
    {
        $this->tokenManager->deleteCompositionToken($token);
    }
}
