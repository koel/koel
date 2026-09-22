<?php

namespace App\Values;

use App\Helpers\Ulid;
use App\Hooks\Action;
use App\Hooks\Filter;

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
