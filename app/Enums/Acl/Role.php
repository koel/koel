<?php

namespace App\Enums\Acl;

use App\Exceptions\KoelPlusRequiredException;
use App\Facades\License;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

enum Role: string implements Arrayable
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::MANAGER => 'Manager',
            self::USER => 'User',
        };
    }

    public static function default(): self
    {
        return self::USER;
    }

    public function level(): int
    {
        return match ($this) {
            self::ADMIN => 3,
            self::MANAGER => 2,
            self::USER => 1,
        };
    }

    public function greaterThan(self $other): bool
    {
        return $this->level() > $other->level();
    }

    public function lessThan(self $other): bool
    {
        return $this->level() < $other->level();
    }

    public function canManage(self $other): bool
    {
        return $this->level() >= $other->level();
    }

    public function available(): bool
    {
        return match ($this) {
            self::ADMIN, self::USER => true,
            self::MANAGER => once(static fn () => License::isPlus()),
        };
    }

    public function assertAvailable(): void
    {
        throw_unless($this->available(), KoelPlusRequiredException::class);
    }

    /** @return Collection<self> */
    public static function allAvailable(): Collection
    {
        return collect(self::cases())->filter(static fn (Role $role) => $role->available());
    }

    public function description(): string
    {
        $isCommunity = once(static fn () => License::isCommunity());

        return match ($this) {
            self::ADMIN => 'Admins can manage everything.',
            self::MANAGER => $isCommunity
                ? 'Managers can manage users, upload musics, and perform other management tasks.'
                : 'Managers can manage users and perform other management tasks.',
            self::USER => $isCommunity
                ? 'Users can play music and manage their own playlists.'
                : 'Users can upload and manage their own music.',
        };
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'id' => $this->value,
            'label' => $this->label(),
            'level' => $this->level(),
            'is_default' => $this === self::default(),
            'description' => $this->description(),
        ];
    }
}
