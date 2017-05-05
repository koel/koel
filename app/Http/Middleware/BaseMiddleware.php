<?php

namespace App\Http\Middleware;

use App\JWTAuth;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\ResponseFactory;
use Tymon\JWTAuth\Middleware\BaseMiddleware as JWTBaseMiddleware;

abstract class BaseMiddleware extends JWTBaseMiddleware
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ResponseFactory $response, Dispatcher $events, JWTAuth $auth)
    {
        parent::__construct($response, $events, $auth);
    }
}
