<?php

namespace App\Services\Auth;

use App\Exceptions\InvalidLoginTokenException;
use App\Models\Passkey;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey as BasePasskey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use SensitiveParameter;
use Webauthn\Exception\WebauthnException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

class PasskeyService
{
    private const int CEREMONY_TTL_SECONDS = 300;

    public function __construct(
        private readonly GenerateVerificationOptions $generateVerificationOptions,
        private readonly VerifyPasskey $verifyPasskey,
        private readonly GenerateRegistrationOptions $generateRegistrationOptions,
        private readonly StorePasskey $storePasskey,
        private readonly DeletePasskey $deletePasskey,
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * @return array{options: array<string, mixed>, login_token: string}
     */
    public function generateLoginOptions(): array
    {
        $options = ($this->generateVerificationOptions)();
        $loginToken = Str::random(32);

        Cache::set(
            cache_key('passkey login options', $loginToken),
            WebAuthn::toJson($options),
            self::CEREMONY_TTL_SECONDS,
        );

        return [
            'options' => WebAuthn::toBrowserArray($options),
            'login_token' => $loginToken,
        ];
    }

    public function verifyLogin(#[SensitiveParameter] string $loginToken, PublicKeyCredential $credential): User
    {
        $serializedOptions = Cache::pull(cache_key('passkey login options', $loginToken));
        throw_unless($serializedOptions, InvalidLoginTokenException::create());

        self::ensureOriginIsAllowed($credential);

        try {
            $passkey = ($this->verifyPasskey)($credential, WebAuthn::fromJson(
                $serializedOptions,
                PublicKeyCredentialRequestOptions::class,
            ));
        } catch (WebauthnException) {
            throw InvalidPasskeyException::make('Unable to verify this passkey.');
        }

        return $this->userRepository->getOne($passkey->user_id);
    }

    /**
     * @return array<string, mixed>
     */
    public function generateRegistrationOptions(User $user): array
    {
        $options = ($this->generateRegistrationOptions)($user);

        Cache::set(
            cache_key('passkey registration options', $user->id),
            WebAuthn::toJson($options),
            self::CEREMONY_TTL_SECONDS,
        );

        return WebAuthn::toBrowserArray($options);
    }

    public function registerPasskey(User $user, string $name, PublicKeyCredential $credential): BasePasskey
    {
        $serializedOptions = Cache::pull(cache_key('passkey registration options', $user->id));
        throw_unless($serializedOptions, InvalidPasskeyException::make('Passkey setup timed out. Please try again.'));

        self::ensureOriginIsAllowed($credential);

        try {
            return ($this->storePasskey)(
                $user,
                $name,
                $credential,
                WebAuthn::fromJson($serializedOptions, PublicKeyCredentialCreationOptions::class),
            );
        } catch (WebauthnException) {
            throw InvalidPasskeyException::make('Unable to register this passkey.');
        }
    }

    public function deletePasskey(User $user, Passkey $passkey): void
    {
        ($this->deletePasskey)($user, $passkey);
    }

    private static function ensureOriginIsAllowed(PublicKeyCredential $credential): void
    {
        $allowedOrigins = array_map(self::originOf(...), Passkeys::allowedOrigins());

        throw_unless(
            in_array(self::originOf($credential->response->clientDataJSON->origin), $allowedOrigins, true),
            InvalidPasskeyException::make(sprintf('Passkeys only work at %s.', $allowedOrigins[0])),
        );
    }

    private static function originOf(string $url): string
    {
        $uri = Uri::of($url);
        $port = $uri->port();

        return $port
            ? sprintf('%s://%s:%d', $uri->scheme(), $uri->host(), $port)
            : sprintf('%s://%s', $uri->scheme(), $uri->host());
    }
}
