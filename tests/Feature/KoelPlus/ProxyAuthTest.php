<?php

namespace Tests\Feature\KoelPlus;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\PlusTestCase;

use function Tests\create_user;

class ProxyAuthTest extends PlusTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config([
            'koel.proxy_auth.enabled' => true,
            'koel.proxy_auth.allow_list' => ['192.168.1.0/24'],
            'koel.proxy_auth.user_header' => 'remote-user',
            'koel.proxy_auth.preferred_name_header' => 'remote-preferred-name',
        ]);

        // Disable Vite so that the test can run without a frontend build.
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        config([
            'koel.proxy_auth.enabled' => false,
            'koel.proxy_auth.allow_list' => [],
            'koel.proxy_auth.user_header' => 'remote-user',
            'koel.proxy_auth.preferred_name_header' => 'remote-preferred-name',
        ]);

        parent::tearDown();
    }

    #[Test]
    public function proxyAuthenticateNewUser(): void
    {
        $response = $this->get('/', [
            'REMOTE_ADDR' => '192.168.1.127',
            'remote-user' => '123456',
            'remote-preferred-name' => 'Bruce Dickinson',
        ]);

        $response->assertOk();
        $response->assertViewHas('token');

        /** @var array $token */
        $token = $response->viewData('token');

        self::assertNotNull(PersonalAccessToken::findToken($token['token']));

        $this->assertDatabaseHas(User::class, [
            'email' => '123456@reverse.proxy',
            'name' => 'Bruce Dickinson',
            'sso_id' => '123456',
            'sso_provider' => 'Reverse Proxy',
        ]);
    }

    #[Test]
    public function proxyAuthenticateExistingUser(): void
    {
        $user = create_user([
            'sso_id' => '123456',
            'sso_provider' => 'Reverse Proxy',
        ]);

        $response = $this->get('/', [
            'REMOTE_ADDR' => '192.168.1.127',
            'remote-user' => '123456',
            'remote-preferred-name' => 'Bruce Dickinson',
        ]);

        $response->assertOk();
        $response->assertViewHas('token');

        /** @var array $token */
        $token = $response->viewData('token');

        self::assertTrue($user->is(PersonalAccessToken::findToken($token['token'])->tokenable));
    }

    #[Test]
    public function proxyAuthenticateWithDisallowedIp(): void
    {
        Log::spy();

        $response = $this->get('/', [
            'REMOTE_ADDR' => '255.168.1.127',
            'remote-user' => '123456',
            'remote-preferred-name' => 'Bruce Dickinson',
        ]);

        $response->assertOk();
        self::assertNull($response->viewData('token'));

        Log::shouldHaveReceived('warning')
            ->withArgs(
                static fn (string $message, array $context) => (
                    str_contains($message, 'Remote address not in allow list')
                    && $context['remote_addr'] === '255.168.1.127'
                ),
            )
            ->once();
    }

    #[Test]
    public function proxyAuthenticateWithMissingUserHeader(): void
    {
        Log::spy();

        $response = $this->get('/', [
            'REMOTE_ADDR' => '192.168.1.127',
            'remote-preferred-name' => 'Bruce Dickinson',
        ]);

        $response->assertOk();
        self::assertNull($response->viewData('token'));

        Log::shouldHaveReceived('warning')
            ->withArgs(
                static fn (string $message, array $context) => (
                    str_contains($message, 'User header not present')
                    && $context['expected_header'] === 'remote-user'
                ),
            )
            ->once();
    }

    #[Test]
    public function proxyAuthenticationIsDisabledWhenAllowListEmpty(): void
    {
        config(['koel.proxy_auth.allow_list' => ['']]);
        Log::spy();

        $response = $this->get('/', [
            'REMOTE_ADDR' => '192.168.1.127',
            'remote-user' => '123456',
        ]);

        $response->assertOk();
        self::assertNull($response->viewData('token'));

        Log::shouldHaveReceived('warning')
            ->withArgs(static fn (string $message) => str_contains($message, 'Remote address not in allow list'))
            ->once();
    }

    #[Test]
    public function proxyAuthenticationPreservesEmailWhenIdentifierIsValidEmail(): void
    {
        $response = $this->get('/', [
            'REMOTE_ADDR' => '192.168.1.127',
            'remote-user' => 'bruce@iron.com',
            'remote-preferred-name' => 'Bruce Dickinson',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas(User::class, [
            'email' => 'bruce@iron.com',
            'name' => 'Bruce Dickinson',
            'sso_id' => 'bruce@iron.com',
            'sso_provider' => 'Reverse Proxy',
        ]);
    }

    #[Test]
    public function proxyAuthenticationFallsBackToIdentifierWhenPreferredNameMissing(): void
    {
        $response = $this->get('/', [
            'REMOTE_ADDR' => '192.168.1.127',
            'remote-user' => 'alice',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas(User::class, [
            'email' => 'alice@reverse.proxy',
            'name' => 'alice',
            'sso_id' => 'alice',
            'sso_provider' => 'Reverse Proxy',
        ]);
    }

    #[Test]
    public function proxyAuthenticationLogsErrorWhenProvisioningThrows(): void
    {
        Log::spy();

        $this->mock(UserService::class, static function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('createOrUpdateUserFromSso')->andThrow(new RuntimeException('provisioning blew up'));
        });

        $response = $this->get('/', [
            'REMOTE_ADDR' => '192.168.1.127',
            'remote-user' => 'alice',
        ]);

        $response->assertOk();
        self::assertNull($response->viewData('token'));

        Log::shouldHaveReceived('error')
            ->withArgs(
                static fn (string $message, array $context) => (
                    str_contains($message, 'Failed to create or update user from SSO headers')
                    && $context['expected_header'] === 'remote-user'
                    && $context['remote_addr'] === '192.168.1.127'
                ),
            )
            ->once();
    }
}
