<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

use function Tests\create_user;

class AuthTest extends TestCase
{
    public function testLogIn(): void
    {
        create_user([
            'email' => 'koel@koel.dev',
            'password' => Hash::make('secret'),
        ]);

        $this->post('api/me', [
            'email' => 'koel@koel.dev',
            'password' => 'secret',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'audio-token',
            ]);

        $this->post('api/me', [
            'email' => 'koel@koel.dev',
            'password' => 'wrong-secret',
        ])
            ->assertUnauthorized();
    }

    public function testLogOut(): void
    {
        $user = create_user([
            'email' => 'koel@koel.dev',
            'password' => Hash::make('secret'),
        ]);

        $response = $this->post('api/me', [
            'email' => 'koel@koel.dev',
            'password' => 'secret',
        ]);

        self::assertSame(2, $user->tokens()->count()); // 1 for API, 1 for audio token

        $this->withToken($response->json('token'))
            ->delete('api/me')
            ->assertNoContent();

        self::assertSame(0, $user->tokens()->count());
    }
}
