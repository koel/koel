<?php

namespace Tests\Feature\Subsonic;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class PingTest extends TestCase
{
    #[Test]
    public function pingReturnsOkXmlByDefault(): void
    {
        $user = create_user();

        $response = $this->get('/rest/ping.view?apiKey=' . $user->subsonic_api_key)->assertOk()->assertHeader(
            'Content-Type',
            'application/xml',
        );

        $xml = simplexml_load_string($response->getContent());

        self::assertSame('ok', (string) $xml['status']);
        self::assertSame('1.16.1', (string) $xml['version']);
        self::assertSame('koel', (string) $xml['type']);
        self::assertSame('true', (string) $xml['openSubsonic']);
    }

    #[Test]
    public function pingReturnsOkJsonWhenRequested(): void
    {
        $user = create_user();

        $response = $this
            ->getJson('/rest/ping.view?apiKey=' . $user->subsonic_api_key . '&f=json')
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertJsonPath('subsonic-response.version', '1.16.1')
            ->assertJsonPath('subsonic-response.type', 'koel')
            ->assertJsonPath('subsonic-response.openSubsonic', true);
    }

    #[Test]
    public function missingApiKeyReturnsCode10Error(): void
    {
        $response = $this->getJson('/rest/ping.view?f=json')->assertOk();

        $response->assertJsonPath('subsonic-response.status', 'failed');
        $response->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function invalidApiKeyReturnsCode40Error(): void
    {
        $response = $this->getJson('/rest/ping.view?apiKey=not-a-real-key&f=json')->assertOk();

        $response->assertJsonPath('subsonic-response.status', 'failed');
        $response->assertJsonPath('subsonic-response.error.code', 40);
    }

    #[Test]
    public function userObserverPopulatesApiKeyOnCreate(): void
    {
        $user = User::factory()->createOne();

        self::assertNotNull($user->subsonic_api_key);
    }

    #[Test]
    public function jsonpWrapsResponseInValidCallback(): void
    {
        $user = create_user();

        $response = $this->get(
            '/rest/ping.view?apiKey=' . $user->subsonic_api_key . '&f=jsonp&callback=myCallback',
        )->assertOk();

        $body = $response->getContent();
        self::assertStringContainsString('myCallback(', $body);
        self::assertStringEndsWith(');', $body);
    }

    #[Test]
    public function jsonpRejectsCallbackWithXssPayload(): void
    {
        $user = create_user();

        $response = $this->get(
            '/rest/ping.view?apiKey=' . $user->subsonic_api_key . '&f=jsonp&callback='
                . urlencode('</script><script>evil()'),
        )->assertOk();

        self::assertStringNotContainsString('<script>', $response->getContent());
        $response->assertJsonPath('subsonic-response.status', 'failed');
        $response->assertJsonPath('subsonic-response.error.code', 10);
    }
}
