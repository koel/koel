<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Stateful Domains
     |--------------------------------------------------------------------------
     |
     | Requests from the following domains / hosts will receive stateful API
     | authentication cookies. Typically, these should include your local
     | and production domains which access your API via a frontend SPA.
     |
     */

    'stateful' => [],

    /*
     |--------------------------------------------------------------------------
     | Sanctum Guards
     |--------------------------------------------------------------------------
     |
     | Sanctum checks these guards before looking for an access token, and a user
     | found through them gets a token with every ability. Koel authenticates
     | with access tokens only, so that each token keeps its own abilities.
     |
     */

    'guard' => [],

    /*
     |--------------------------------------------------------------------------
     | Expiration Minutes
     |--------------------------------------------------------------------------
     |
     | This value controls the number of minutes until an issued token will be
     | considered expired. If this value is null, personal access tokens do
     | not expire. This won't tweak the lifetime of first-party sessions.
     |
     */

    'expiration' => null,

    /*
     |--------------------------------------------------------------------------
     | Sanctum Middleware
     |--------------------------------------------------------------------------
     |
     | When authenticating your first-party SPA with Sanctum you may need to
     | customize some of the middleware Sanctum uses while processing the
     | request. You may change the middleware listed below as required.
     |
     */

    'middleware' => [],
];
