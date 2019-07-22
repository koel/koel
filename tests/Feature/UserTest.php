<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Mockery\MockInterface;

class UserTest extends TestCase
{
    /** @var MockInterface */
    private $hash;

    public function setUp(): void
    {
        parent::setUp();
        $this->hash = $this->mockIocDependency(Hasher::class);
    }

    public function testNonAdminCannotCreateUser(): void
    {
        $this->postAsUser('api/user', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'qux',
        ])->seeStatusCode(403);
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
            ], factory(User::class, 'admin')->create());

        self::seeInDatabase('users', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'hashed',
        ]);
    }

    public function testAdminUpdatesUser(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create([
            'name' => 'John',
            'email' => 'john@doe.com',
            'password' => 'nope',
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
            ], factory(User::class, 'admin')->create());

        self::seeInDatabase('users', [
            'id' => $user->id,
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'hashed',
        ]);
    }

    public function testAdminDeletesUser(): void
    {
        $user = factory(User::class)->create();
        $admin = factory(User::class, 'admin')->create();

        $this->deleteAsUser("api/user/{$user->id}", [], $admin)
            ->notSeeInDatabase('users', ['id' => $user->id]);
    }

    public function testSeppukuNotAllowed(): void
    {
        $admin = factory(User::class, 'admin')->create();

        // A user can't delete himself
        $this->deleteAsUser("api/user/{$admin->id}", [], $admin)
            ->seeStatusCode(403)
            ->seeInDatabase('users', ['id' => $admin->id]);
    }

    public function testUpdateUserProfile(): void
    {
        $user = factory(User::class)->create();
        $this->assertNull($user->getPreference('foo'));

        $user->setPreference('foo', 'bar');
        $this->assertEquals('bar', $user->getPreference('foo'));

        $user->deletePreference('foo');
        $this->assertNull($user->getPreference('foo'));
    }
}
