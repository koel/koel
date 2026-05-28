<?php

namespace Tests\Feature\Subsonic;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class LegacyAuthTest extends TestCase
{
    #[Test]
    public function clearTextPasswordWithEmailUsernameSucceeds(): void
    {
        $user = create_user();

        $this
            ->getJson(sprintf('/rest/ping.view?u=%s&p=%s&f=json', urlencode($user->email), $user->subsonic_api_key))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');
    }

    #[Test]
    public function clearTextPasswordWithWrongKeyIsRejected(): void
    {
        $user = create_user();

        $this
            ->getJson(sprintf('/rest/ping.view?u=%s&p=not-the-real-key&f=json', urlencode($user->email)))
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 40);
    }

    #[Test]
    public function hexEncodedPasswordSucceeds(): void
    {
        $user = create_user();

        $this
            ->getJson(sprintf(
                '/rest/ping.view?u=%s&p=enc:%s&f=json',
                urlencode($user->email),
                bin2hex($user->subsonic_api_key),
            ))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');
    }

    #[Test]
    public function hexEncodedPasswordWithInvalidHexIsRejected(): void
    {
        $user = create_user();

        $this
            ->getJson(sprintf('/rest/ping.view?u=%s&p=enc:nothex&f=json', urlencode($user->email)))
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 40);
    }

    #[Test]
    public function hexEncodedPasswordWithWrongKeyIsRejected(): void
    {
        $user = create_user();

        $this
            ->getJson(sprintf(
                '/rest/ping.view?u=%s&p=enc:%s&f=json',
                urlencode($user->email),
                bin2hex('not-the-real-key'),
            ))
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 40);
    }

    #[Test]
    public function saltedTokenSucceeds(): void
    {
        $user = create_user();
        $salt = 'c19b2d';
        $token = md5($user->subsonic_api_key . $salt);

        $this
            ->getJson(sprintf('/rest/ping.view?u=%s&t=%s&s=%s&f=json', urlencode($user->email), $token, $salt))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');
    }

    #[Test]
    public function saltedTokenAcceptsUppercaseHexFromClient(): void
    {
        $user = create_user();
        $salt = 'C19B2D';
        $token = strtoupper(md5($user->subsonic_api_key . $salt));

        $this
            ->getJson(sprintf('/rest/ping.view?u=%s&t=%s&s=%s&f=json', urlencode($user->email), $token, $salt))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');
    }

    #[Test]
    public function saltedTokenWithWrongHashIsRejected(): void
    {
        $user = create_user();

        $this
            ->getJson(sprintf(
                '/rest/ping.view?u=%s&t=%s&s=%s&f=json',
                urlencode($user->email),
                md5('wrong-key.c19b2d'),
                'c19b2d',
            ))
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 40);
    }

    #[Test]
    public function tokenWithoutSaltIsRejectedAsMissingParameter(): void
    {
        $user = create_user();

        $this
            ->getJson(sprintf(
                '/rest/ping.view?u=%s&t=%s&f=json',
                urlencode($user->email),
                md5($user->subsonic_api_key . 'irrelevant'),
            ))
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function unknownUsernameIsRejected(): void
    {
        create_user();

        $this
            ->getJson('/rest/ping.view?u=ghost@example.com&p=anything&f=json')
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 40);
    }

    #[Test]
    public function missingUsernameIsRejectedAsMissingParameter(): void
    {
        $this
            ->getJson('/rest/ping.view?p=anything&f=json')
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function usernameWithoutAnyCredentialIsRejectedAsMissingParameter(): void
    {
        $user = create_user();

        $this
            ->getJson(sprintf('/rest/ping.view?u=%s&f=json', urlencode($user->email)))
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function apiKeyTakesPrecedenceOverLegacyCredentials(): void
    {
        $user = create_user();

        $this
            ->getJson(sprintf(
                '/rest/ping.view?apiKey=%s&u=ghost@example.com&p=anything&f=json',
                $user->subsonic_api_key,
            ))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');
    }

    #[Test]
    public function tokenTakesPrecedenceOverPasswordWhenBothPresent(): void
    {
        $user = create_user();
        $salt = 'c19b2d';
        $token = md5($user->subsonic_api_key . $salt);

        $this
            ->getJson(sprintf(
                '/rest/ping.view?u=%s&t=%s&s=%s&p=wrong-but-ignored&f=json',
                urlencode($user->email),
                $token,
                $salt,
            ))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');
    }
}
