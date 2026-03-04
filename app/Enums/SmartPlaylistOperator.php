<?php

namespace App\Enums;

enum SmartPlaylistOperator: string
{
    case IS = 'is';
    case IS_NOT = 'isNot';
    case CONTAINS = 'contains';
    case NOT_CONTAIN = 'notContain';
    case IS_BETWEEN = 'isBetween';
    case IS_GREATER_THAN = 'isGreaterThan';
    case IS_LESS_THAN = 'isLessThan';
    case BEGINS_WITH = 'beginsWith';
    case ENDS_WITH = 'endsWith';
    case IN_LAST = 'inLast';
    case NOT_IN_LAST = 'notInLast';
    case IS_NOT_BETWEEN = 'isNotBetween';

    public function toWhereMethod(): string
    {
        return match ($this) {
            self::IS_BETWEEN => 'whereBetween',
            self::IS_NOT_BETWEEN => 'whereNotBetween',
            default => 'where',
        };
    }

    public function isNegative(): bool
    {
        return in_array($this, [self::IS_NOT, self::NOT_CONTAIN, self::IS_NOT_BETWEEN], true);
    }
}
