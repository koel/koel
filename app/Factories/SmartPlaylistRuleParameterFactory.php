<?php

namespace App\Factories;

use App\Models\Rule;
use Webmozart\Assert\Assert;

class SmartPlaylistRuleParameterFactory
{
    /**
     * @param array<mixed> $value
     *
     * @return array<string>
     */
    public function createParameters(string $model, string $operator, array $value): array
    {
        $ruleParameterMap = [
            Rule::OPERATOR_BEGINS_WITH => [$model, 'LIKE', "$value[0]%"],
            Rule::OPERATOR_ENDS_WITH => [$model, 'LIKE', "%$value[0]"],
            Rule::OPERATOR_IS => [$model, '=', $value[0]],
            Rule::OPERATOR_IS_NOT => [$model, '<>', $value[0]],
            Rule::OPERATOR_CONTAINS => [$model, 'LIKE', "%$value[0]%"],
            Rule::OPERATOR_NOT_CONTAIN => [$model, 'NOT LIKE', "%$value[0]%"],
            Rule::OPERATOR_IS_LESS_THAN => [$model, '<', $value[0]],
            Rule::OPERATOR_IS_GREATER_THAN => [$model, '>', $value[0]],
            Rule::OPERATOR_IS_BETWEEN => [$model, $value],
            Rule::OPERATOR_NOT_IN_LAST => static fn (): array => [$model, '<', now()->subDays($value[0])],
            Rule::OPERATOR_IN_LAST => static fn (): array => [$model, '>=', now()->subDays($value[0])],
        ];

        Assert::keyExists($ruleParameterMap, $operator);

        return is_callable($ruleParameterMap[$operator])
            ? $ruleParameterMap[$operator]()
            : $ruleParameterMap[$operator];
    }
}
