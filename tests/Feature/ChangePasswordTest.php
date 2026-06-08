<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ChangePasswordTest extends TestCase
{
    #[Test]
    public function changePasswordWithValidCurrentPassword(): void
    {
        $user = create_user(['password' => Hash::make('old-secret')]);

        $token = $this
            ->putAs('api/me/password', ['current_password' => 'old-secret', 'new_password' => 'new-secret-1234'], $user)
            ->assertNoContent()
            ->headers->get('Authorization');

        self::assertNotNull($token);

        $user->refresh();
        self::assertTrue(Hash::check('new-secret-1234', $user->password));
    }

    #[Test]
    public function changePasswordWithInvalidCurrentPasswordFails(): void
    {
        $user = create_user(['password' => Hash::make('old-secret')]);

        $this->putAs(
            'api/me/password',
            ['current_password' => 'wrong', 'new_password' => 'new-secret-1234'],
            $user,
        )->assertUnprocessable();

        $user->refresh();
        self::assertTrue(Hash::check('old-secret', $user->password));
    }

    #[Test]
    public function changePasswordRequiresBothFields(): void
    {
        $user = create_user();

        $this->putAs('api/me/password', ['current_password' => 'old-secret'], $user)->assertUnprocessable();
        $this->putAs('api/me/password', ['new_password' => 'new-secret-1234'], $user)->assertUnprocessable();
    }

    #[Test]
    public function disabledInDemo(): void
    {
        config(['koel.misc.demo' => true]);
        $user = create_user(['password' => Hash::make('old-secret')]);

        $this->putAs(
            'api/me/password',
            ['current_password' => 'old-secret', 'new_password' => 'new-secret-1234'],
            $user,
        )->assertNoContent();
    }
}
