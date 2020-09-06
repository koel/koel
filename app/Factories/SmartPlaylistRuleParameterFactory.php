<?php

namespace App\Factories;

use App\Models\Rule;
use Carbon\Carbon;
use InvalidArgumentException;
use Throwable;

class SmartPlaylistRuleParameterFactory
{
    /**
     * @param mixed[] $value
     *
     * @throws Throwable
     *
     * @return string[]
     */
    public function createParameters(string $model, string $operator, array $value): array
    {
        $ruleParameterMap = [
            Rule::OPERATOR_BEGINS_WITH => [$model, 'LIKE', "{$value[0]}%"],
            Rule::OPERATOR_ENDS_WITH => [$model, 'LIKE', "%{$value[0]}"],
            Rule::OPERATOR_IS => [$model, '=', $value[0]],
            Rule::OPERATOR_IS_NOT => [$model, '<>', $value[0]],
            Rule::OPERATOR_CONTAINS => [$model, 'LIKE', "%{$value[0]}%"],
            Rule::OPERATOR_NOT_CONTAIN => [$model, 'NOT LIKE', "%{$value[0]}%"],
            Rule::OPERATOR_IS_LESS_THAN => [$model, '<', $value[0]],
            Rule::OPERATOR_IS_GREATER_THAN => [$model, '>', $value[0]],
            Rule::OPERATOR_IS_BETWEEN => [$model, $value],
            Rule::OPERATOR_NOT_IN_LAST => static function () use ($model, $value): array {
                return [$model, '<', (new Carbon())->subDay($value[0])];
            },
            Rule::OPERATOR_IN_LAST => static function () use ($model, $value): array {
                return [$model, '>=', (new Carbon())->subDay($value[0])];
            },
        ];

        throw_unless(array_key_exists($operator, $ruleParameterMap), InvalidArgumentException::class, sprintf(
            'Invalid operator %s. Valid operators are: %s.',
            $operator,
            implode(', ', array_keys($ruleParameterMap))
        ));

        return is_array($ruleParameterMap[$operator]) ? $ruleParameterMap[$operator] : $ruleParameterMap[$operator]();
    }
}
