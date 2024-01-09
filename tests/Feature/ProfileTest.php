<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

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
        /** @var User $user */
        $user =  User::factory()->create(['password' => Hash::make('secret')]);

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
        /** @var User $user */
        $user =  User::factory()->create(['password' => Hash::make('secret')]);

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
}
