<?php

namespace App\Values\Pagination;

use Illuminate\Database\Query\Expression;

final readonly class CursorSortKey
{
    private function __construct(
        public Expression $expression,
        public mixed $value,
        public bool $ascending,
        public bool $nullsComeAfter,
    ) {}

    public static function make(Expression $expression, mixed $value, bool $ascending, bool $nullsComeAfter): self
    {
        return new self(expression: $expression, value: $value, ascending: $ascending, nullsComeAfter: $nullsComeAfter);
    }
}
