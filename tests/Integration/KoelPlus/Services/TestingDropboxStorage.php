<?php

namespace Tests\Integration\KoelPlus\Services;

use Illuminate\Support\Facades\Http;

trait TestingDropboxStorage
{
    private static function mockDropboxRefreshAccessTokenCall(string $token = 'free-bird', int $expiresIn = 3600): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://api.dropboxapi.com/oauth2/token' => Http::response([
                'access_token' => $token,
                'expires_in' => $expiresIn,
            ]),
        ]);
    }
}
