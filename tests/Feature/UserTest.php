<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

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
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

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
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        /** @var User $user */
        $user = User::factory()->admin()->create(['password' => 'secret']);

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
        /** @var User $user */
        $user = User::factory()->create();

        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $this->deleteAs("api/user/$user->id", [], $admin);
        self::assertModelMissing($user);
    }

    public function testSeppukuNotAllowed(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        // A user can't delete himself
        $this->deleteAs("api/user/$admin->id", [], $admin)->assertForbidden();
        self::assertModelExists($admin);
    }
}
