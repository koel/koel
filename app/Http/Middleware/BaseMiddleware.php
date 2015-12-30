<?php

namespace App\Http\Middleware;

use App\JWTAuth;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\ResponseFactory;
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
