<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class UserTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    public function testCreateUser()
    {
        // Non-admins can't do shit
        $this->postAsUser('api/user', [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
                'password' => 'qux',
            ])
            ->seeStatusCode(403);

        // But admins can
        $this->postAsUser('api/user', [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
                'password' => 'qux',
            ], factory(User::class, 'admin')->create());

        $this->seeInDatabase('users', ['name' => 'Foo']);
    }

    public function testUpdateUser()
    {
        $user = factory(User::class)->create();

        $this->putAsUser("api/user/{$user->id}", [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
                'password' => 'qux',
            ], factory(User::class, 'admin')->create());

        $this->seeInDatabase('users', ['name' => 'Foo', 'email' => 'bar@baz.com']);
    }

    public function testDeleteUser()
    {
        $user = factory(User::class)->create();
        $admin = factory(User::class, 'admin')->create();

        $this->deleteAsUser("api/user/{$user->id}", [], $admin)
            ->notSeeInDatabase('users', ['id' => $user->id]);

        // A user can't delete himself
        $this->deleteAsUser("api/user/{$admin->id}", [], $admin)
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
