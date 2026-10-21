<?php

namespace Tests\Feature;

use App\Services\Auth\TokenManager;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ChangePasswordTest extends TestCase
{
    #[Test]
    public function changePasswordWithValidCurrentPassword(): void
    {
        $user = create_user(['password' => Hash::make('old-secret')]);

        $token = $this
            ->putAs('api/me/password', ['current_password' => 'old-secret', 'new_password' => 'new-secret-1234'], $user)
            ->assertNoContent()
            ->headers->get('Authorization');

        self::assertNotNull($token);

        $user->refresh();
        self::assertTrue(Hash::check('new-secret-1234', $user->password));
    }

    #[Test]
    public function changePasswordRevokesOtherSessionsButKeepsTheCurrentOne(): void
    {
        $user = create_user(['password' => Hash::make('old-secret')]);
        $currentSession = app(TokenManager::class)->createCompositeToken($user);
        $otherSession = app(TokenManager::class)->createCompositeToken($user);

        $newApiToken = $this
            ->withToken($currentSession->apiToken)
            ->putJson('api/me/password', ['current_password' => 'old-secret', 'new_password' => 'new-secret-1234'])
            ->assertNoContent()
            ->headers->get('Authorization');

        self::assertNull(PersonalAccessToken::findToken($otherSession->apiToken));
        self::assertNull(PersonalAccessToken::findToken($otherSession->audioToken));
        self::assertNotNull(PersonalAccessToken::findToken($currentSession->audioToken));

        $this->app['auth']->forgetGuards();

        $this->withToken($newApiToken)->getJson('api/me')->assertOk();
    }

    #[Test]
    public function changePasswordWithInvalidCurrentPasswordFails(): void
    {
        $user = create_user(['password' => Hash::make('old-secret')]);

        $this->putAs(
            'api/me/password',
            ['current_password' => 'wrong', 'new_password' => 'new-secret-1234'],
            $user,
        )->assertUnprocessable();

        $user->refresh();
        self::assertTrue(Hash::check('old-secret', $user->password));
    }

    #[Test]
    public function changePasswordRequiresBothFields(): void
    {
        $user = create_user();

        $this->putAs('api/me/password', ['current_password' => 'old-secret'], $user)->assertUnprocessable();
        $this->putAs('api/me/password', ['new_password' => 'new-secret-1234'], $user)->assertUnprocessable();
    }

    #[Test]
    public function disabledInDemo(): void
    {
        config(['koel.misc.demo' => true]);
        $user = create_user(['password' => Hash::make('old-secret')]);

        $this->putAs(
            'api/me/password',
            ['current_password' => 'old-secret', 'new_password' => 'new-secret-1234'],
            $user,
        )->assertNoContent();

        $user->refresh();
        self::assertTrue(Hash::check('old-secret', $user->password));
    }
}
