<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class ProfileTest extends TestCase
{
    use WithoutMiddleware;

    public function testUpdate()
    {
        $user = factory(User::class)->create();
        $this->putAsUser('api/me', ['name' => 'Foo', 'email' => 'bar@baz.com'], $user);

        $this->seeInDatabase('users', ['name' => 'Foo', 'email' => 'bar@baz.com']);
    }
}
