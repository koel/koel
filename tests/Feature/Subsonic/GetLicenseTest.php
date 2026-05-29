<?php

namespace Tests\Feature\Subsonic;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetLicenseTest extends TestCase
{
    #[Test]
    public function returnsValidLicenseXml(): void
    {
        $user = create_user(['email' => 'alice@example.com']);

        $response = $this->get('/rest/getLicense.view?apiKey=' . $user->subsonic_api_key)->assertOk()->assertHeader(
            'Content-Type',
            'application/xml',
        );

        $xml = simplexml_load_string($response->getContent());

        self::assertSame('ok', (string) $xml['status']);
        self::assertSame('true', (string) $xml->license['valid']);
        self::assertSame('alice@example.com', (string) $xml->license['email']);
    }

    #[Test]
    public function returnsValidLicenseJson(): void
    {
        $user = create_user(['email' => 'bob@example.com']);

        $this
            ->getJson('/rest/getLicense.view?apiKey=' . $user->subsonic_api_key . '&f=json')
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertJsonPath('subsonic-response.license.valid', true)
            ->assertJsonPath('subsonic-response.license.email', 'bob@example.com');
    }
}
