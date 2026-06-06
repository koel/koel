<?php

namespace Tests\Feature\Subsonic;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ShowApiKeyCommandTest extends TestCase
{
    #[Test]
    public function printsApiKeyForExistingUser(): void
    {
        $user = create_user(['email' => 'alice@example.com']);

        $this
            ->artisan('koel:subsonic:apikey', ['email' => 'alice@example.com'])
            ->expectsOutput($user->subsonic_api_key)
            ->assertSuccessful();
    }

    #[Test]
    public function failsWhenUserNotFound(): void
    {
        $this->artisan('koel:subsonic:apikey', ['email' => 'ghost@example.com'])->assertFailed();
    }
}
