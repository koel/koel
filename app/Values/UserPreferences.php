<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final class UserPreferences implements Arrayable, JsonSerializable
{
    private function __construct(public ?string $lastFmSessionKey = null)
    {
    }

    public static function make(?string $lastFmSessionKey = null): self
    {
        return new self($lastFmSessionKey);
    }

    /** @return array<mixed> */
    public function toArray(): array
    {
        return ['lastfm_session_key' => $this->lastFmSessionKey];
    }

    /** @return array<mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
