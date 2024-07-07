<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\read_as_data_url;
use function Tests\test_path;

class ProfileTest extends TestCase
{
    public function testUpdateProfileRequiresCurrentPassword(): void
    {
        $this->putAs('api/me', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
        ])
            ->assertUnprocessable();
    }

    public function testUpdateProfileWithoutNewPassword(): void
    {
        $user = create_user(['password' => Hash::make('secret')]);

        $this->putAs('api/me', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'current_password' => 'secret',
        ], $user);

        $user->refresh();

        self::assertSame('Foo', $user->name);
        self::assertSame('bar@baz.com', $user->email);
        self::assertTrue(Hash::check('secret', $user->password));
    }

    public function testUpdateProfileWithNewPassword(): void
    {
        $user = create_user(['password' => Hash::make('secret')]);

        $token = $this->putAs('api/me', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'new_password' => 'new-secret',
            'current_password' => 'secret',
        ], $user)
            ->headers
            ->get('Authorization');

        $user->refresh();

        self::assertNotNull($token);
        self::assertSame('Foo', $user->name);
        self::assertSame('bar@baz.com', $user->email);
        self::assertTrue(Hash::check('new-secret', $user->password));
    }

    public function testUpdateProfileWithAvatar(): void
    {
        $user = create_user(['password' => Hash::make('secret')]);
        self::assertNull($user->getRawOriginal('avatar'));

        $this->putAs('api/me', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'current_password' => 'secret',
            'avatar' => read_as_data_url(test_path('blobs/cover.png')),
        ], $user)
            ->assertOk();

        $user->refresh();

        self::assertFileExists(user_avatar_path($user->getRawOriginal('avatar')));
    }

    public function testUpdateProfileRemovingAvatar(): void
    {
        $user = create_user([
            'password' => Hash::make('secret'),
            'email' => 'foo@bar.com',
            'avatar' => 'foo.jpg',
        ]);

        $this->putAs('api/me', [
            'name' => 'Foo',
            'email' => 'foo@bar.com',
            'current_password' => 'secret',
        ], $user)
            ->assertOk();

        $user->refresh();

        self::assertNull($user->getRawOriginal('avatar'));
    }
}
