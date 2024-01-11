<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class UserTest extends TestCase
{
    public function testNonAdminCannotCreateUser(): void
    {
        $this->postAs('api/user', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'secret',
            'is_admin' => false,
        ])->assertForbidden();
    }

    public function testAdminCreatesUser(): void
    {
        $admin = create_admin();

        $this->postAs('api/user', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'secret',
            'is_admin' => true,
        ], $admin)
            ->assertSuccessful();

        /** @var User $user */
        $user = User::query()->firstWhere('email', 'bar@baz.com');

        self::assertTrue(Hash::check('secret', $user->password));
        self::assertSame('Foo', $user->name);
        self::assertSame('bar@baz.com', $user->email);
        self::assertTrue($user->is_admin);
    }

    public function testAdminUpdatesUser(): void
    {
        $admin = create_admin();
        $user = create_admin(['password' => 'secret']);

        $this->putAs("api/user/$user->id", [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'new-secret',
            'is_admin' => false,
        ], $admin)
            ->assertSuccessful();

        $user->refresh();

        self::assertTrue(Hash::check('new-secret', $user->password));
        self::assertSame('Foo', $user->name);
        self::assertSame('bar@baz.com', $user->email);
        self::assertFalse($user->is_admin);
    }

    public function testAdminDeletesUser(): void
    {
        $user = create_user();

        $this->deleteAs("api/user/$user->id", [], create_admin());
        self::assertModelMissing($user);
    }

    public function testSelfDeletionNotAllowed(): void
    {
        $admin = create_admin();

        $this->deleteAs("api/user/$admin->id", [], $admin)->assertForbidden();
        self::assertModelExists($admin);
    }
}
