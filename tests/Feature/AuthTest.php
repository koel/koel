<?php

namespace Tests\Feature;

use App\Models\RadioStation;
use App\Services\Auth\AuthenticationService;
use App\Services\Auth\TokenManager;
use App\Services\Radio\RadioStreamService;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

        $this
            ->post('api/me', [
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
        ])->assertUnauthorized();
    }

    #[Test]
    public function loginViaOneTimeToken(): void
    {
        $user = create_user();
        $authService = app(AuthenticationService::class);
        $token = $authService->generateOneTimeToken($user);

        $this
            ->post('api/me/otp', ['token' => $token])
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'audio-token',
            ]);
    }

    #[Test]
    public function unauthenticatedNavigateRequestRedirectsToRootInsteadOfThrowing(): void
    {
        // Without redirectGuestsTo configured, Laravel 12's Authenticate middleware would try
        // to resolve route('login') and blow up with RouteNotFoundException because koel has
        // no login route. See issue #2164.
        $this->get('api/data', ['Accept' => 'text/html'])->assertRedirect('/');
    }

    #[Test]
    public function unauthenticatedJsonRequestReturnsCleanUnauthorized(): void
    {
        $this->getJson('api/data')->assertUnauthorized()->assertJson(['error' => 'Unauthenticated.']);
    }

    #[Test]
    public function audioTokenCannotAccessApiAsBearerToken(): void
    {
        $token = app(TokenManager::class)->createCompositeToken(create_user());

        $this->withToken($token->audioToken)->getJson('api/me')->assertUnauthorized();
    }

    #[Test]
    public function audioTokenCannotAccessApiAsQueryParameter(): void
    {
        $token = app(TokenManager::class)->createCompositeToken(create_user());

        $this->getJson("api/me?t=$token->audioToken")->assertUnauthorized();
        $this->getJson("api/me?api_token=$token->audioToken")->assertUnauthorized();
    }

    #[Test]
    public function apiTokenCannotAccessApiAsQueryParameter(): void
    {
        $token = app(TokenManager::class)->createCompositeToken(create_user());

        $this->getJson("api/me?t=$token->apiToken")->assertUnauthorized();
        $this->getJson("api/me?api_token=$token->apiToken")->assertUnauthorized();
    }

    #[Test]
    public function audioTokenStreamsRadioAsQueryParameter(): void
    {
        $user = create_user();
        $station = RadioStation::factory()->for($user)->createOne();
        $token = app(TokenManager::class)->createCompositeToken($user);

        $this
            ->mock(RadioStreamService::class)
            ->expects('stream')
            ->with(Mockery::on($station->is(...)))
            ->andReturn(new StreamedResponse());

        $this->get("radio/stream/{$station->id}?t=$token->audioToken")->assertOk();
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

        $this->withToken($response->json('token'))->delete('api/me')->assertNoContent();

        self::assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function logOutOtherSessions(): void
    {
        $user = create_user([
            'email' => 'koel@koel.dev',
            'password' => Hash::make('secret'),
        ]);

        $credentials = ['email' => 'koel@koel.dev', 'password' => 'secret'];
        $currentSession = $this->post('api/me', $credentials)->json();
        $this->post('api/me', $credentials);
        $this->post('api/me', $credentials);

        self::assertSame(6, $user->tokens()->count());

        $this->withToken($currentSession['token'])->delete('api/me/sessions/others')->assertNoContent();

        self::assertSame(2, $user->tokens()->count());
        self::assertNotNull(PersonalAccessToken::findToken($currentSession['token']));
        self::assertNotNull(PersonalAccessToken::findToken($currentSession['audio-token']));
    }
}
