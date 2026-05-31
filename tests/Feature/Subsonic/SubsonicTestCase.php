<?php

namespace Tests\Feature\Subsonic;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

abstract class SubsonicTestCase extends TestCase
{
    /**
     * Build a Subsonic API URL with the standard `apiKey` + `f=json` auth/format
     * params plus any endpoint-specific extras.
     *
     * @param array<string, mixed> $extra
     */
    protected static function urlFor(string $endpoint, User $user, array $extra = []): string
    {
        return '/rest/'
        . $endpoint
        . '?'
        . Arr::query(array_merge([
            'apiKey' => $user->subsonic_api_key,
            'f' => 'json',
        ], $extra));
    }

    /** @param array<string, mixed> $extra */
    protected function getSubsonic(string $endpoint, User $user, array $extra = []): TestResponse
    {
        return $this->getJson(self::urlFor($endpoint, $user, $extra));
    }

    protected static function assertSubsonicOk(TestResponse $response): void
    {
        $response->assertOk()->assertJsonPath('subsonic-response.status', 'ok');
    }

    protected static function assertSubsonicErrorCode(TestResponse $response, int $code): void
    {
        $response
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', $code);
    }
}
