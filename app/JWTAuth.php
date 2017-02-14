<?php

namespace App;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth as BaseJWTAuth;
use Tymon\JWTAuth\JWTManager;
use Tymon\JWTAuth\Providers\Auth\AuthInterface;
use Tymon\JWTAuth\Providers\User\UserInterface;

class JWTAuth extends BaseJWTAuth
{
    /**
     * {@inheritdoc}
     */
    public function __construct(JWTManager $manager, UserInterface $user, AuthInterface $auth, Request $request)
    {
        parent::__construct($manager, $user, $auth, $request);
    }

    /**
     * {@inheritdoc}
     */
    public function parseToken($method = 'bearer', $header = 'authorization', $query = 'jwt-token')
    {
        return parent::parseToken($method, $header, $query);
    }
}
