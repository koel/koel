<?php

namespace Tests\Feature\Commands;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class SetUserRoleCommandTest extends TestCase
{
    #[Test]
    public function setUserRole(): void
    {
        $user = create_user(['email' => 'jane@example.com']);

        $this
            ->artisan('koel:admin:set-user-role', ['email' => 'jane@example.com'])
            ->expectsChoice('What role should the user have?', 'admin', [
                'admin' => 'Admin',
                'user' => 'User',
            ])
            ->assertSuccessful();

        $user->refresh();
        self::assertTrue($user->hasRole('admin'));
    }

    #[Test]
    public function failForNonExistentUser(): void
    {
        $this->artisan('koel:admin:set-user-role', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
