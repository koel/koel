<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Mockery\MockInterface;

class ProfileTest extends TestCase
{
    /** @var MockInterface */
    private $hash;

    public function setUp()
    {
        parent::setUp();

        $this->hash = $this->mockIocDependency(Hasher::class);
    }

    public function testUpdateProfileWithoutPassword()
    {
        $user = factory(User::class)->create();

        $this->hash->shouldReceive('make')->never();

        $this->putAsUser('api/me', ['name' => 'Foo', 'email' => 'bar@baz.com'], $user);

        $this->seeInDatabase('users', ['name' => 'Foo', 'email' => 'bar@baz.com']);
    }

    public function testUpdateProfileWithPassword()
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        $this->hash
            ->shouldReceive('make')
            ->once()
            ->with('qux')
            ->andReturn('hashed');

        $this->putAsUser('api/me', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'qux',
        ], $user);

        $this->seeInDatabase('users', [
            'id' => $user->id,
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'hashed',
        ]);
    }
}
