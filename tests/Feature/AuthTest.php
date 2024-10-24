<?php

namespace Tests\Feature;

use App\Services\AuthenticationService;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class AuthTest extends TestCase
{
    #[Test]
    public function logIn(): void
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

    #[Test]
    public function loginViaOneTimeToken(): void
    {
        $user = create_user();
        $authService = app(AuthenticationService::class);
        $token = $authService->generateOneTimeToken($user);

        $this->post('api/me/otp', ['token' => $token])
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'audio-token',
            ]);
    }

    #[Test]
    public function logOut(): void
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
