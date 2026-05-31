<?php

namespace Tests\Feature\Subsonic;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

abstract class SubsonicTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        TestResponse::macro('assertSubsonicOk', function (): TestResponse {
            /** @var TestResponse $this */
            return $this->assertOk()->assertJsonPath('subsonic-response.status', 'ok');
        });

        TestResponse::macro('assertSubsonicErrorCode', function (int $code): TestResponse {
            /** @var TestResponse $this */
            return $this
                ->assertOk()
                ->assertJsonPath('subsonic-response.status', 'failed')
                ->assertJsonPath('subsonic-response.error.code', $code);
        });
    }

    /** @param array<string, mixed> $extra */
    protected function getSubsonic(string $endpoint, User $user, array $extra = []): TestResponse
    {
        $query = Arr::query(array_merge([
            'apiKey' => $user->subsonic_api_key,
            'f' => 'json',
        ], $extra));

        return $this->getJson("/rest/$endpoint?$query");
    }
}
