<?php

namespace Tests\Feature\KoelPlus;

use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class ChangePasswordTest extends PlusTestCase
{
    #[Test]
    public function changePasswordForbiddenForSsoUsers(): void
    {
        $user = create_user([
            'password' => Hash::make('old-secret'),
            'sso_provider' => 'Google',
            'sso_id' => 'abc123',
        ]);

        $this->putAs(
            'api/me/password',
            ['current_password' => 'old-secret', 'new_password' => 'new-secret-1234'],
            $user,
        )->assertForbidden();

        $user->refresh();
        self::assertTrue(Hash::check('old-secret', $user->password));
    }
}
