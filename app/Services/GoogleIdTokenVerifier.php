<?php

namespace App\Services;

use App\Values\User\SsoUser;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class GoogleIdTokenVerifier
{
    private const CERTS_URL = 'https://www.googleapis.com/oauth2/v3/certs';
    private const VALID_ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    public function __construct(
        #[Config('services.google.client_id')] private readonly string $clientId,
    ) {}

    public function verify(string $idToken): SsoUser
    {
        $keys = $this->getPublicKeys();
        $payload = JWT::decode($idToken, $keys);

        if (!in_array($payload->iss, self::VALID_ISSUERS, true)) {
            throw new UnexpectedValueException('Invalid issuer');
        }

        if ($payload->aud !== $this->clientId) {
            throw new UnexpectedValueException('Invalid audience');
        }

        return SsoUser::fromArray([
            'provider' => 'Google',
            'id' => $payload->sub,
            'email' => $payload->email,
            'name' => $payload->name ?? $payload->email,
            'avatar' => $payload->picture ?? null,
        ]);
    }

    private function getPublicKeys(): array
    {
        $jwks = Cache::remember('google-jwk-keys', 3600, static function (): array {
            $response = Http::timeout(10)->get(self::CERTS_URL);

            if ($response->failed()) {
                throw new UnexpectedValueException('Failed to fetch Google public keys');
            }

            return $response->json();
        });

        return JWK::parseKeySet($jwks, 'RS256');
    }
}
