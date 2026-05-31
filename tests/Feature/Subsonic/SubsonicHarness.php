<?php

namespace Tests\Feature\Subsonic;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;

final class SubsonicHarness
{
    public function __construct(
        private readonly TestCase $test,
    ) {}

    /** @param array<string, mixed> $extra */
    public function get(string $endpoint, User $user, array $extra = []): TestResponse
    {
        $query = Arr::query(array_merge([
            'apiKey' => $user->subsonic_api_key,
            'f' => 'json',
        ], $extra));

        return $this->test->getJson("/rest/$endpoint?$query");
    }

    public function assertOk(TestResponse $response): TestResponse
    {
        return $response->assertOk()->assertJsonPath('subsonic-response.status', 'ok');
    }

    public function assertErrorCode(TestResponse $response, int $code): TestResponse
    {
        return $response
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', $code);
    }
}
