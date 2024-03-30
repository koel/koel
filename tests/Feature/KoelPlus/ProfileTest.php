<?php

namespace Tests\Feature\KoelPlus;

use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\read_as_data_url;
use function Tests\test_path;

class ProfileTest extends PlusTestCase
{
    public function testUpdateSSOProfile(): void
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
            'avatar' => read_as_data_url(test_path('blobs/cover.png')),
        ], $user)->assertOk();

        $user->refresh();

        self::assertSame('Bruce Dickinson', $user->name);
        self::assertSame('user@koel.dev', $user->email); // email should not be updated
        self::assertTrue($user->has_custom_avatar);
    }
}
