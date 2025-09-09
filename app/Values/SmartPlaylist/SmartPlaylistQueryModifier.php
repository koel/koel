<?php

namespace App\Values\SmartPlaylist;

use App\Builders\SongBuilder;
use App\Enums\SmartPlaylistModel as Model;
use App\Enums\SmartPlaylistOperator as Operator;
use App\Values\SmartPlaylist\SmartPlaylistRule as Rule;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;

final class SmartPlaylistQueryModifier
{
    private static function resolveWhereMethod(Rule $rule, Operator $operator): string
    {
        return $rule->model->requiresRawQuery()
            ? 'whereRaw'
            : $operator->toWhereMethod();
    }

    public static function applyRule(Rule $rule, SongBuilder $query): void
    {
        $operator = $rule->operator;
        $value = $rule->value;

        // If the rule is a date rule and the operator is "is" or "is not", we need to
        // convert the date to a range of dates and use the "between" or "not between" operator instead,
        // as we store dates as timestamps in the database.
        // For instance, "IS 2023-10-01" should be converted to "IS BETWEEN 2023-10-01 AND 2023-10-02."
        if ($rule->model->isDate() && in_array($operator, [Operator::IS, Operator::IS_NOT], true)) {
            $operator = $operator === Operator::IS ? Operator::IS_BETWEEN : Operator::IS_NOT_BETWEEN;

            $nextDay = Carbon::createFromFormat('Y-m-d', $value[0])->addDay()->format('Y-m-d');
            $value = [$value[0], $nextDay];
        }

        if ($rule->model->getManyToManyRelation()) {
            // for a many-to-many relation (like genres), we need to use a subquery with "whereHas" or "whereDoesntHave"
            $whereHasClause = $rule->operator->isNegative() ? 'whereDoesntHave' : 'whereHas';

            // Also, since the inclusion logic has already been handled by "whereHas" and "whereDoesntHave", the
            // negative operators should be converted to their positive counterparts (isNot becomes is,
            // notContain becomes contains, isNotBetween becomes isBetween).
            // This way, for example, "genre is not 'Rock'" will be translated to
            // "whereDoesntHave genre where genre is 'Rock'".
            $operator = match ($operator) {
                Operator::IS_NOT => Operator::IS,
                Operator::NOT_CONTAIN => Operator::CONTAINS,
                Operator::IS_NOT_BETWEEN => Operator::IS_BETWEEN,
                default => $operator,
            };

            $query->{$whereHasClause}(
                $rule->model->getManyToManyRelation(),
                static function (Builder $subQuery) use ($rule, $operator, $value): void {
                    $subQuery->{self::resolveWhereMethod($rule, $operator)}(
                        ...self::generateParameters($rule->model, $operator, $value)
                    );
                }
            );
        } else {
            $query->{self::resolveWhereMethod($rule, $operator)}(
                ...self::generateParameters($rule->model, $operator, $value)
            );
        }
    }

    /** @inheritdoc */
    private static function generateParameters(Model $model, Operator $operator, array $value): array
    {
        $column = $model->toColumnName();

        $parameters = $model->requiresRawQuery()
            ? self::generateRawParameters($column, $operator, $value)
            : self::generateEloquentParameters($column, $operator, $value);

        return $parameters instanceof Closure ? $parameters() : $parameters;
    }

    /** @inheritdoc */
    private static function generateRawParameters(string $column, Operator $operator, array $value): array|Closure
    {
        // For raw parameters like those for play count, we need to use raw SQL clauses (whereRaw).
        // whereRaw() expects a string for the statement and an array of parameters for binding.
        return match ($operator) {
            Operator::BEGINS_WITH => ["$column LIKE ?", ["{$value[0]}%"]],
            Operator::CONTAINS => ["$column LIKE ?", ["%{$value[0]}%"]],
            Operator::ENDS_WITH => ["$column LIKE ?", ["%{$value[0]}"]],
            Operator::IN_LAST => static fn () => ["$column >= ?", [now()->subDays($value[0])]],
            Operator::IS => ["$column = ?", [$value[0]]],
            Operator::IS_BETWEEN => ["$column BETWEEN ? AND ?", $value],
            Operator::IS_GREATER_THAN => ["$column > ?", [$value[0]]],
            Operator::IS_LESS_THAN => ["$column < ?", [$value[0]]],
            Operator::IS_NOT => ["$column <> ?", [$value[0]]],
            Operator::IS_NOT_BETWEEN => ["$column NOT BETWEEN ? AND ?", $value],
            Operator::NOT_CONTAIN => ["$column NOT LIKE ?", ["%{$value[0]}%"]],
            Operator::NOT_IN_LAST => static fn () => ["$column < ?", [now()->subDays($value[0])]],
        };
    }

    private static function generateEloquentParameters(string $column, Operator $operator, array $value): array|Closure
    {
        return match ($operator) {
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
    }
}
