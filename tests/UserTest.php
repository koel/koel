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

    public function testUpdateProfile()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user)
            ->put('api/me', ['name' => 'Foo', 'email' => 'bar@baz.com']);

        $this->seeInDatabase('users', ['name' => 'Foo', 'email' => 'bar@baz.com']);
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
        $this->actingAs(factory(User::class, 'admin')->create())
            ->delete("api/user/{$user->id}");

        $this->notSeeInDatabase('users', ['id' => $user->id]);
    }
}
