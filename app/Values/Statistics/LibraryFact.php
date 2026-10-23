<?php

namespace App\Values\Statistics;

use App\Enums\LibraryFactUnit;

final readonly class LibraryFact
{
    private function __construct(
        public string $key,
        public string $label,
        public int $value,
        public LibraryFactUnit $unit,
        public ?int $used,
        public ?int $limit,
    ) {}

    public static function make(
        string $key,
        string $label,
        int $value,
        LibraryFactUnit $unit = LibraryFactUnit::COUNT,
        ?int $used = null,
        ?int $limit = null,
    ): self {
        return new self(key: $key, label: $label, value: $value, unit: $unit, used: $used, limit: $limit);
    }
}
