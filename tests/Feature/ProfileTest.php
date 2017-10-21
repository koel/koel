<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class ProfileTest extends TestCase
{
    use WithoutMiddleware;

    /** @test */
    public function user_can_update_his_profile()
    {
        $user = factory(User::class)->create();
        $this->putAsUser('api/me', ['name' => 'Foo', 'email' => 'bar@baz.com'], $user);

        $this->seeInDatabase('users', ['name' => 'Foo', 'email' => 'bar@baz.com']);
    }
}
