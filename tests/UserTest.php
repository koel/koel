<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class UserTest extends TestCase
{
    use WithoutMiddleware, DatabaseTransactions;

    public function testCreateUser()
    {
        // Non-admins can't do shit
        $this->actingAs(factory(User::class)->create())
            ->post('api/user', [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
                'password' => 'qux',
            ])
            ->seeStatusCode(403);

        // But admins can
        $this->actingAs(factory(User::class, 'admin')->create())
            ->post('api/user', [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
                'password' => 'qux',
            ]);

        $this->seeInDatabase('users', ['name' => 'Foo']);
    }

    public function testUpdateUser()
    {
        $user = factory(User::class)->create();

        $this->actingAs(factory(User::class, 'admin')->create())
            ->put("api/user/{$user->id}", [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
                'password' => 'qux',
            ]);

        $this->seeInDatabase('users', ['name' => 'Foo', 'email' => 'bar@baz.com']);
    }

    public function testDeleteUser()
    {
        $user = factory(User::class)->create();
        $admin = factory(User::class, 'admin')->create();

        $this->actingAs($admin)
            ->delete("api/user/{$user->id}")
            ->notSeeInDatabase('users', ['id' => $user->id]);

        // A user can't delete himself
        $this->actingAs($admin)
            ->delete("api/user/{$admin->id}")
            ->seeStatusCode(403)
            ->seeInDatabase('users', ['id' => $admin->id]);
    }

    public function testUserPreferences()
    {
        $user = factory(User::class)->create();
        $this->assertNull($user->getPreference('foo'));

        $user->setPreference('foo', 'bar');
        $this->assertEquals('bar', $user->getPreference('foo'));

        $user->deletePreference('foo');
        $this->assertNull($user->getPreference('foo'));
    }
}
