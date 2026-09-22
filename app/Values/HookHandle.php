<?php

namespace App\Values;

use App\Enums\Hooks\Action;
use App\Enums\Hooks\Filter;
use App\Helpers\Ulid;

final readonly class HookHandle
{
    private function __construct(
        public Action|Filter $hook,
        public string $id,
    ) {}

    public static function make(Action|Filter $hook): self
    {
        return new self($hook, Ulid::generate());
    }
}
