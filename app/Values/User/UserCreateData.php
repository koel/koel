<?php

namespace App\Values\User;

use App\Enums\Acl\Role;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Hash;

final readonly class UserCreateData implements Arrayable
{
    public string $password;

    public function __construct(
        public string $name,
        public string $email,
        ?string $plainTextPassword,
        public Role $role,
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
            role: Role::default(),
            avatar: $ssoUser->avatar,
            ssoId: $ssoUser->id,
            ssoProvider: $ssoUser->provider,
        );
    }

    public static function make(
        string $name,
        string $email,
        ?string $plainTextPassword = null,
        ?Role $role = null,
        ?string $avatar = null,
        ?string $ssoId = null,
        ?string $ssoProvider = null,
    ): self {
        return new self(
            name: $name,
            email: $email,
            plainTextPassword: $plainTextPassword,
            role: $role ?? Role::default(),
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
            'role' => $this->role->value,
            'avatar' => $this->avatar,
            'sso_id' => $this->ssoId,
            'sso_provider' => $this->ssoProvider,
        ];
    }
}
