<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class ProfileTest extends TestCase
{
    use WithoutMiddleware, DatabaseTransactions;

    public function testUpdate()
    {
        $this->actingAs(factory(User::class)->create())
            ->put('api/me', ['name' => 'Foo', 'email' => 'bar@baz.com']);

        $this->seeInDatabase('users', ['name' => 'Foo', 'email' => 'bar@baz.com']);
    }
}
