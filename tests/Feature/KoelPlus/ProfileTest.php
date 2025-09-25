<?php

namespace Tests\Feature\KoelPlus;

use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class ProfileTest extends PlusTestCase
{
    #[Test]
    public function updateSsoProfile(): void
    {
        $user = create_user([
            'sso_provider' => 'Google',
            'sso_id' => '123',
            'email' => 'user@koel.dev',
            'name' => 'SSO User',
            'avatar' => null,
            // no current password required for SSO users
        ]);

        self::assertTrue($user->is_sso);
        self::assertFalse($user->has_custom_avatar);

        $this->putAs('api/me', [
            'name' => 'Bruce Dickinson',
            'email' => 'bruce@iron.com',
            'avatar' => minimal_base64_encoded_image(),
        ], $user)->assertOk();

        $user->refresh();

        self::assertSame('Bruce Dickinson', $user->name);
        self::assertSame('user@koel.dev', $user->email); // email should not be updated
        self::assertTrue($user->has_custom_avatar);
    }
}
