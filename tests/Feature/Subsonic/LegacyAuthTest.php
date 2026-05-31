<?php

namespace Tests\Feature\Subsonic;

use Illuminate\Support\Arr;
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
            ->getJson(
                '/rest/ping.view?'
                    . Arr::query([
                        'u' => $user->email,
                        'p' => $user->subsonic_api_key,
                        'f' => 'json',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');
    }

    #[Test]
    public function clearTextPasswordWithWrongKeyIsRejected(): void
    {
        $user = create_user();

        $this
            ->getJson(
                '/rest/ping.view?'
                    . Arr::query([
                        'u' => $user->email,
                        'p' => 'not-the-real-key',
                        'f' => 'json',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 40);
    }

    #[Test]
    public function hexEncodedPasswordSucceeds(): void
    {
        $user = create_user();

        $this
            ->getJson(
                '/rest/ping.view?'
                    . Arr::query([
                        'u' => $user->email,
                        'p' => 'enc:' . bin2hex($user->subsonic_api_key),
                        'f' => 'json',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');
    }

    #[Test]
    public function hexEncodedPasswordWithInvalidHexIsRejected(): void
    {
        $user = create_user();

        $this
            ->getJson(
                '/rest/ping.view?'
                    . Arr::query([
                        'u' => $user->email,
                        'p' => 'enc:nothex',
                        'f' => 'json',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 40);
    }

    #[Test]
    public function hexEncodedPasswordWithWrongKeyIsRejected(): void
    {
        $user = create_user();

        $this
            ->getJson(
                '/rest/ping.view?'
                    . Arr::query([
                        'u' => $user->email,
                        'p' => 'enc:' . bin2hex('not-the-real-key'),
                        'f' => 'json',
                    ]),
            )
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
            ->getJson(
                '/rest/ping.view?'
                    . Arr::query([
                        'u' => $user->email,
                        't' => $token,
                        's' => $salt,
                        'f' => 'json',
                    ]),
            )
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
            ->getJson(
                '/rest/ping.view?'
                    . Arr::query([
                        'u' => $user->email,
                        't' => $token,
                        's' => $salt,
                        'f' => 'json',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');
    }

    #[Test]
    public function saltedTokenWithWrongHashIsRejected(): void
    {
        $user = create_user();

        $this
            ->getJson(
                '/rest/ping.view?'
                    . Arr::query([
                        'u' => $user->email,
                        't' => md5('wrong-key.c19b2d'),
                        's' => 'c19b2d',
                        'f' => 'json',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 40);
    }

    #[Test]
    public function tokenWithoutSaltIsRejectedAsMissingParameter(): void
    {
        $user = create_user();

        $this
            ->getJson(
                '/rest/ping.view?'
                    . Arr::query([
                        'u' => $user->email,
                        't' => md5($user->subsonic_api_key . 'irrelevant'),
                        'f' => 'json',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function unknownUsernameIsRejected(): void
    {
        create_user();

        $this
            ->getJson(
                '/rest/ping.view?'
                    . Arr::query([
                        'u' => 'ghost@example.com',
                        'p' => 'anything',
                        'f' => 'json',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 40);
    }

    #[Test]
    public function tokenAuthFailsClosedWhenStoredKeyIsNull(): void
    {
        $user = create_user();
        $user->forceFill(['subsonic_api_key' => null, 'subsonic_api_key_hash' => null])->saveQuietly();

        $salt = 'attack';
        $forgedToken = md5($salt);

        $this
            ->getJson(
                '/rest/ping.view?'
                    . Arr::query([
                        'u' => $user->email,
                        't' => $forgedToken,
                        's' => $salt,
                        'f' => 'json',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 40);
    }

    #[Test]
    public function passwordAuthFailsClosedWhenStoredKeyIsNull(): void
    {
        $user = create_user();
        $user->forceFill(['subsonic_api_key' => null, 'subsonic_api_key_hash' => null])->saveQuietly();

        $this
            ->getJson(
                '/rest/ping.view?'
                    . Arr::query([
                        'u' => $user->email,
                        'p' => 'anything-the-attacker-tries',
                        'f' => 'json',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 40);
    }

    #[Test]
    public function missingUsernameIsRejectedAsMissingParameter(): void
    {
        $this
            ->getJson('/rest/ping.view?' . Arr::query(['p' => 'anything', 'f' => 'json']))
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function usernameWithoutAnyCredentialIsRejectedAsMissingParameter(): void
    {
        $user = create_user();

        $this
            ->getJson('/rest/ping.view?' . Arr::query(['u' => $user->email, 'f' => 'json']))
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function apiKeyTakesPrecedenceOverLegacyCredentials(): void
    {
        $user = create_user();

        $this
            ->getJson(
                '/rest/ping.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'u' => 'ghost@example.com',
                        'p' => 'anything',
                        'f' => 'json',
                    ]),
            )
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
            ->getJson(
                '/rest/ping.view?'
                    . Arr::query([
                        'u' => $user->email,
                        't' => $token,
                        's' => $salt,
                        'p' => 'wrong-but-ignored',
                        'f' => 'json',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');
    }
}
