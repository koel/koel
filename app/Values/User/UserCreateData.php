<?php

namespace App\Values\User;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Hash;

final readonly class UserCreateData implements Arrayable
{
    public string $password;

    public function __construct(
        public string $name,
        public string $email,
        ?string $plainTextPassword = null,
        public bool $isAdmin = false,
        public ?string $avatar = null,
        public ?string $ssoId = null,
        public ?string $ssoProvider = null,
    ) {
        if ($ssoProvider) {
            SsoUser::assertValidProvider($ssoProvider);
        }

        $this->password = $plainTextPassword ? Hash::make($plainTextPassword) : '';
    }

    public static function fromSsoUser(SsoUser $ssoUser): self
    {
        return new self(
            name: $ssoUser->name,
            email: $ssoUser->email,
            plainTextPassword: '',
            isAdmin: false,
            avatar: $ssoUser->avatar,
            ssoId: $ssoUser->id,
            ssoProvider: $ssoUser->provider,
        );
    }

    public static function make(
        string $name,
        string $email,
        ?string $plainTextPassword = null,
        bool $isAdmin = false,
        ?string $avatar = null,
        ?string $ssoId = null,
        ?string $ssoProvider = null,
    ): self {
        return new self(
            name: $name,
            email: $email,
            plainTextPassword: $plainTextPassword,
            isAdmin: $isAdmin,
            avatar: $avatar,
            ssoId: $ssoId,
            ssoProvider: $ssoProvider,
        );
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'is_admin' => $this->isAdmin,
            'avatar' => $this->avatar,
            'sso_id' => $this->ssoId,
            'sso_provider' => $this->ssoProvider,
        ];
    }
}
