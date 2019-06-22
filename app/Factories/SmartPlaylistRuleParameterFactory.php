<?php

namespace App\Factories;

use App\Models\Rule;
use Carbon\Carbon;
use InvalidArgumentException;

class SmartPlaylistRuleParameterFactory
{
    public function createParameters(string $model, string $operator, array $value): array
    {
        switch ($operator) {
            case Rule::OPERATOR_BEGINS_WITH:
                return [$model, 'LIKE', "{$value[0]}%"];
            case Rule::OPERATOR_ENDS_WITH:
                return [$model, 'LIKE', "%{$value[0]}"];
            case Rule::OPERATOR_IS:
                return [$model, '=', $value[0]];
            case Rule::OPERATOR_NOT_IN_LAST:
                return [$model, '<', (new Carbon())->subDay($value[0])];
            case Rule::OPERATOR_NOT_CONTAIN:
                return [$model, 'NOT LIKE', "%{$value[0]}%"];
            case Rule::OPERATOR_IS_NOT:
                return [$model, '<>', $value[0]];
            case Rule::OPERATOR_IS_LESS_THAN:
                return [$model, '<', $value[0]];
            case Rule::OPERATOR_IS_GREATER_THAN:
                return [$model, '>', $value[0]];
            case Rule::OPERATOR_IS_BETWEEN:
                return [$model, $value];
            case Rule::OPERATOR_IN_LAST:
                return [$model, '>=', (new Carbon())->subDay($value[0])];
            case Rule::OPERATOR_CONTAINS:
                return [$model, 'LIKE', "%{$value[0]}%"];
            default:
                // should never reach here actually
                throw new InvalidArgumentException('Invalid operator.');
        }
    }
}
