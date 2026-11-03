<?php

namespace App\Models\Concerns\Users;

use App\Enums\Acl\Role as RoleEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Arr;

trait HasUserAttributes
{
    protected function avatar(): Attribute
    {
        return Attribute::get(fn (): string => avatar_or_gravatar(
            Arr::get($this->attributes, 'avatar'),
            $this->email,
        ))->shouldCache();
    }

    protected function hasCustomAvatar(): Attribute
    {
        return Attribute::get(fn () => (bool) $this->getRawOriginal('avatar'))->shouldCache();
    }

    protected function isProspect(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) $this->invitation_token);
    }

    protected function canSetPasswordWithoutCurrentOne(): Attribute
    {
        return Attribute::get(fn (): bool => !$this->password);
    }

    protected function connectedToLastfm(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) $this->preferences->lastFmSessionKey)->shouldCache();
    }

    protected function role(): Attribute
    {
        // Enforce a single-role permission model
        return Attribute::make(get: function () {
            $role = $this->getRoleNames();

            if ($role->isEmpty()) {
                return RoleEnum::default();
            }

            return RoleEnum::tryFrom($role->sole()) ?? RoleEnum::default();
        });
    }
}
