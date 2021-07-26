<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class ProfileTest extends TestCase
{
    /** @var User */
    private $user;

    public function setUp(): void
    {
        parent::setUp();

        // @phpstan-ignore-next-line
        $this->user = User::factory()->create(['password' => Hash::make('secret')]);
    }

    public function testUpdateProfileRequiresCurrentPassword(): void
    {
        $this->putAsUser('api/me', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
        ], $this->user)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateProfileWithoutNewPassword(): void
    {
        $this->putAsUser('api/me', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'current_password' => 'secret',
        ], $this->user);

        $this->user->refresh();

        self::assertSame('Foo', $this->user->name);
        self::assertSame('bar@baz.com', $this->user->email);
        self::assertTrue(Hash::check('secret', $this->user->password));
    }

    public function testUpdateProfileWithNewPassword(): void
    {
        $this->putAsUser('api/me', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'new_password' => 'new-secret',
            'current_password' => 'secret',
        ], $this->user)
            ->assertJsonStructure(['token']);

        $this->user->refresh();

        self::assertSame('Foo', $this->user->name);
        self::assertSame('bar@baz.com', $this->user->email);
        self::assertTrue(Hash::check('new-secret', $this->user->password));
    }
}
