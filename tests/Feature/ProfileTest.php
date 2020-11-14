<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;

class ProfileTest extends TestCase
{
    private $hash;

    public function setUp(): void
    {
        parent::setUp();

        $this->hash = static::mockIocDependency(Hasher::class);
    }

    public function testUpdateProfileWithoutPassword(): void
    {
        $user = User::factory()->create();

        $this->hash->shouldReceive('make')->never();

        $this->putAsUser('api/me', ['name' => 'Foo', 'email' => 'bar@baz.com'], $user);

        self::assertDatabaseHas('users', ['name' => 'Foo', 'email' => 'bar@baz.com']);
    }

    public function testUpdateProfileWithPassword(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

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

        self::assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'hashed',
        ]);
    }
}
