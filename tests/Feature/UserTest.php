<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testNonAdminCannotCreateUser(): void
    {
        $this->postAsUser('api/user', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'secret',
            'is_admin' => false,
        ])->assertForbidden();
    }

    public function testAdminCreatesUser(): void
    {
        $this->postAsUser('api/user', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'secret',
            'is_admin' => true,
        ], User::factory()->admin()->create())
            ->assertSuccessful();

        /** @var User $user */
        $user = User::firstWhere('email', 'bar@baz.com');

        self::assertTrue(Hash::check('secret', $user->password));
        self::assertSame('Foo', $user->name);
        self::assertSame('bar@baz.com', $user->email);
        self::assertTrue($user->is_admin);
    }

    public function testAdminUpdatesUser(): void
    {
        /** @var User $user */
        $user = User::factory()->admin()->create(['password' => 'secret']);

        $this->putAsUser("api/user/$user->id", [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'new-secret',
            'is_admin' => false,
        ], User::factory()->admin()->create());

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
        $admin = User::factory()->admin()->create();

        $this->deleteAsUser("api/user/$user->id", [], $admin);
        self::assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function testSeppukuNotAllowed(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        // A user can't delete himself
        $this->deleteAsUser("api/user/$admin->id", [], $admin)
            ->assertStatus(403);

        self::assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
