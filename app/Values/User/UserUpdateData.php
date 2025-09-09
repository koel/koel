<?php

namespace App\Values\User;

use Illuminate\Support\Facades\Hash;

final readonly class UserUpdateData
{
    public ?string $password;

    private function __construct(
        public string $name,
        public string $email,
        ?string $plainTextPassword,
        public ?bool $isAdmin,
        public ?string $avatar,
    ) {
        $this->password = $plainTextPassword ? Hash::make($plainTextPassword) : null;
    }

    public static function make(
        string $name,
        string $email,
        ?string $plainTextPassword = null,
        ?bool $isAdmin = null,
        ?string $avatar = null
    ): self {
        return new self(
            name: $name,
            email: $email,
            plainTextPassword: $plainTextPassword,
            isAdmin: $isAdmin,
            avatar: $avatar,
        );
    }
}
