<?php

namespace App\Services\Auth;

use App\Exceptions\InvalidLoginTokenException;
use App\Models\Passkey;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;
use Illuminate\Validation\ValidationException;
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
    private const string USE_REJECTED_MESSAGE = "Passkeys can't be used from this address.";

    public function __construct(
        private readonly GenerateVerificationOptions $generateVerificationOptions,
        private readonly VerifyPasskey $verifyPasskey,
        private readonly GenerateRegistrationOptions $generateRegistrationOptions,
        private readonly StorePasskey $storePasskey,
        private readonly DeletePasskey $deletePasskey,
        private readonly UserRepository $userRepository,
        private readonly TwoFactorAuthenticator $twoFactorAuth,
        private readonly Hasher $hasher,
        private readonly ConfigRepository $config,
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

    public function verifyLogin(
        #[SensitiveParameter]
        string $loginToken,
        PublicKeyCredential $credential,
        string $requestOrigin,
    ): User {
        $serializedOptions = Cache::pull(cache_key('passkey login options', $loginToken));
        throw_unless($serializedOptions, InvalidLoginTokenException::create());

        $this->ensureOriginIsAllowed($credential, $requestOrigin, self::USE_REJECTED_MESSAGE);

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
    public function generateConfirmationOptions(User $user): array
    {
        $options = ($this->generateVerificationOptions)($user);

        Cache::set(
            cache_key('passkey confirmation options', $user->id),
            WebAuthn::toJson($options),
            self::CEREMONY_TTL_SECONDS,
        );

        return WebAuthn::toBrowserArray($options);
    }

    public function confirmIdentityWithPasskey(User $user, PublicKeyCredential $credential, string $requestOrigin): void
    {
        $serializedOptions = Cache::pull(cache_key('passkey confirmation options', $user->id));
        throw_unless($serializedOptions, InvalidPasskeyException::make('Passkey check timed out. Please try again.'));

        $this->ensureOriginIsAllowed($credential, $requestOrigin, self::USE_REJECTED_MESSAGE);

        try {
            ($this->verifyPasskey)(
                $credential,
                WebAuthn::fromJson($serializedOptions, PublicKeyCredentialRequestOptions::class),
                $user,
            );
        } catch (WebauthnException) {
            throw InvalidPasskeyException::make('Unable to verify this passkey.');
        }
    }

    public function confirmIdentityWithPassword(
        User $user,
        #[SensitiveParameter]
        ?string $password,
        #[SensitiveParameter]
        ?string $twoFactorCode,
    ): void {
        if (self::hasNothingToConfirmWith($user)) {
            return;
        }

        throw_unless($password && $this->hasher->check($password, $user->password), ValidationException::withMessages([
            'password' => 'Wrong password.',
        ]));

        throw_if(
            $user->hasTwoFactorEnabled() && !($twoFactorCode && $this->twoFactorAuth->verify($user, $twoFactorCode)),
            ValidationException::withMessages(['code' => 'Wrong two-factor code.']),
        );
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

    public function registerPasskey(
        User $user,
        string $name,
        PublicKeyCredential $credential,
        string $requestOrigin,
    ): BasePasskey {
        $serializedOptions = Cache::pull(cache_key('passkey registration options', $user->id));
        throw_unless($serializedOptions, InvalidPasskeyException::make('Passkey setup timed out. Please try again.'));

        $this->ensureOriginIsAllowed($credential, $requestOrigin, "Passkeys can't be added from this address.");

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

    private static function hasNothingToConfirmWith(User $user): bool
    {
        return $user->sso_provider && !$user->hasPasskeysEnabled();
    }

    private function ensureOriginIsAllowed(
        PublicKeyCredential $credential,
        string $requestOrigin,
        string $rejectionMessage,
    ): void {
        $configuredOrigins = Passkeys::allowedOrigins();
        $credentialOrigin = self::originOf($credential->response->clientDataJSON->origin);

        if (in_array($credentialOrigin, array_map(self::originOf(...), $configuredOrigins), true)) {
            return;
        }

        throw_unless(
            $credentialOrigin === self::originOf($requestOrigin)
            && self::isUnderRelyingPartyId(Uri::of($requestOrigin)->host()),
            InvalidPasskeyException::make($rejectionMessage),
        );

        $this->config->set('passkeys.allowed_origins', [...$configuredOrigins, $credentialOrigin]);
    }

    private static function isUnderRelyingPartyId(?string $host): bool
    {
        $relyingPartyId = Passkeys::relyingPartyId();

        return $host && ($host === $relyingPartyId || str_ends_with($host, ".$relyingPartyId"));
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
