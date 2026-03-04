<?php

namespace App\Values\User;

use App\Enums\Acl\Role;
use Illuminate\Support\Facades\Hash;
use SensitiveParameter;

final readonly class UserUpdateData
{
    public ?string $password;

    private function __construct(
        public string $name,
        public string $email,
        #[SensitiveParameter]
        ?string $plainTextPassword,
        public ?Role $role,
        public ?string $avatar,
    ) {
        $this->password = $plainTextPassword ? Hash::make($plainTextPassword) : null;
    }

    public static function make(
        string $name,
        string $email,
        #[SensitiveParameter] ?string $plainTextPassword = null,
        ?Role $role = null,
        ?string $avatar = null,
    ): self {
        return new self(
            name: $name,
            email: $email,
            plainTextPassword: $plainTextPassword,
            role: $role,
            avatar: $avatar,
        );
    }
}
