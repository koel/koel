<?php

namespace App\Values;

use App\Helpers\Ulid;

final readonly class HookHandle
{
    private function __construct(
        public string $hook,
        public string $id,
    ) {}

    public static function make(string $hook): self
    {
        return new self($hook, Ulid::generate());
    }
}
