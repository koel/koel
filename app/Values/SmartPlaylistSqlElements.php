<?php

namespace App\Values;

use App\Enums\SmartPlaylistModel as Model;
use App\Enums\SmartPlaylistOperator as Operator;
use App\Values\SmartPlaylistRule as Rule;
use Carbon\Carbon;
use Closure;

final class SmartPlaylistSqlElements
{
    public string $clause;
    public array $parameters;

    private function __construct(string $clause, ...$parameters)
    {
        $this->clause = $clause;
        $this->parameters = $parameters;
    }

    public static function fromRule(Rule $rule): self
    {
        $operator = $rule->operator;
        $value = $rule->value;

        // If the rule is a date rule and the operator is "is" or "is not", we need to
        // convert the date to a range of dates and use the "between" or "not between" operator instead,
        // as we store dates as timestamps in the database.
        if ($rule->model->isDate() && in_array($operator, [Operator::IS, Operator::IS_NOT], true)) {
            $operator = $operator === Operator::IS ? Operator::IS_BETWEEN : Operator::IS_NOT_BETWEEN;

            $nextDay = Carbon::createFromFormat('Y-m-d', $value[0])->addDay()->format('Y-m-d');
            $value = [$value[0], $nextDay];
        }

        return new self(
            $operator->toEloquentClause(),
            ...self::generateParameters($rule->model, $operator, $value)
        );
    }

    /** @return array<mixed> */
    private static function generateParameters(Model $model, Operator $operator, array $value): array
    {
        $column = $model->toColumnName();

        $resolver = match ($operator) {
            Operator::BEGINS_WITH => [$column, 'LIKE', "$value[0]%"],
            Operator::ENDS_WITH => [$column, 'LIKE', "%$value[0]"],
            Operator::IS => [$column, '=', $value[0]],
            Operator::IS_NOT => [$column, '<>', $value[0]],
            Operator::CONTAINS => [$column, 'LIKE', "%$value[0]%"],
            Operator::NOT_CONTAIN => [$column, 'NOT LIKE', "%$value[0]%"],
            Operator::IS_LESS_THAN => [$column, '<', $value[0]],
            Operator::IS_GREATER_THAN => [$column, '>', $value[0]],
            Operator::IS_BETWEEN, Operator::IS_NOT_BETWEEN => [$column, $value],
            Operator::NOT_IN_LAST => static fn () => [$column, '<', now()->subDays($value[0])],
            Operator::IN_LAST => static fn () => [$column, '>=', now()->subDays($value[0])],
        };

        return $resolver instanceof Closure ? $resolver() : $resolver;
    }
}
