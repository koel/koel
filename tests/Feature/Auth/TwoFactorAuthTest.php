<?php

namespace Tests\Feature\Auth;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class TwoFactorAuthTest extends TestCase
{
    #[Test]
    public function managementRoutesAreForbiddenOnCommunity(): void
    {
        $user = create_user();

        $this->postAs('api/me/two-factor', [], $user)->assertNotFound();
        $this->postAs('api/me/two-factor/confirm', ['code' => '000000'], $user)->assertNotFound();
        $this->postAs('api/me/two-factor/recovery-codes', ['code' => '000000'], $user)->assertNotFound();
        $this->deleteAs('api/me/two-factor', ['code' => '000000'], $user)->assertNotFound();
    }

    #[Test]
    public function loginWithoutTwoFactorWorksAsBefore(): void
    {
        $user = create_user(['password' => bcrypt('secret')]);

        $response = $this
            ->post('api/me', [
                'email' => $user->email,
                'password' => 'secret',
            ])
            ->assertOk()
            ->json();

        self::assertArrayNotHasKey('requires_two_factor', $response);
        self::assertNotEmpty($response['token']);
    }
}
