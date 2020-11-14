<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;

class UserTest extends TestCase
{
    private $hash;

    public function setUp(): void
    {
        parent::setUp();

        $this->hash = static::mockIocDependency(Hasher::class);
    }

    public function testNonAdminCannotCreateUser(): void
    {
        $this->postAsUser('api/user', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'qux',
            'is_admin' => false,
        ])->assertStatus(403);
    }

    public function testAdminCreatesUser(): void
    {
        $this->hash
            ->shouldReceive('make')
            ->once()
            ->with('qux')
            ->andReturn('hashed');

        $this->postAsUser('api/user', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'qux',
            'is_admin' => true,
        ], User::factory()->admin()->create());

        self::assertDatabaseHas('users', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'hashed',
            'is_admin' => true,
        ]);
    }

    public function testAdminUpdatesUser(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'John',
            'email' => 'john@doe.com',
            'password' => 'nope',
            'is_admin' => true,
        ]);

        $this->hash
            ->shouldReceive('make')
            ->once()
            ->with('qux')
            ->andReturn('hashed');

        $this->putAsUser("api/user/{$user->id}", [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'qux',
            'is_admin' => false,
        ], User::factory()->admin()->create());

        self::assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'hashed',
            'is_admin' => false,
        ]);
    }

    public function testAdminDeletesUser(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->deleteAsUser("api/user/{$user->id}", [], $admin);
        self::assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function testSeppukuNotAllowed(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        // A user can't delete himself
        $this->deleteAsUser("api/user/{$admin->id}", [], $admin)
            ->assertStatus(403);

        self::assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function testUpdateUserProfile(): void
    {
        $user = User::factory()->create();
        self::assertNull($user->getPreference('foo'));

        $user->setPreference('foo', 'bar');
        self::assertEquals('bar', $user->getPreference('foo'));

        $user->deletePreference('foo');
        self::assertNull($user->getPreference('foo'));
    }
}
