<?php

namespace App\Values\Statistics;

use App\Enums\LibraryStatisticUnit;

final readonly class LibraryStatistic
{
    private function __construct(
        public string $key,
        public string $label,
        public int $value,
        public LibraryStatisticUnit $unit,
        public ?int $used,
        public ?int $limit,
    ) {}

    public static function make(
        string $key,
        string $label,
        int $value,
        LibraryStatisticUnit $unit = LibraryStatisticUnit::COUNT,
        ?int $used = null,
        ?int $limit = null,
    ): self {
        return new self(key: $key, label: $label, value: $value, unit: $unit, used: $used, limit: $limit);
    }
}
