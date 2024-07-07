<?php

namespace Tests\Feature\KoelPlus;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
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

    public function testProxyAuthenticateNewUser(): void
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

        self::assertDatabaseHas(User::class, [
            'email' => '123456@reverse.proxy',
            'name' => 'Bruce Dickinson',
            'sso_id' => '123456',
            'sso_provider' => 'Reverse Proxy',
        ]);
    }

    public function testProxyAuthenticateExistingUser(): void
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

    public function testProxyAuthenticateWithDisallowedIp(): void
    {
        $response = $this->get('/', [
            'REMOTE_ADDR' => '255.168.1.127',
            'remote-user' => '123456',
            'remote-preferred-name' => 'Bruce Dickinson',
        ]);

        $response->assertOk();

        self::assertNull($response->viewData('token'));
    }
}
