<?php

namespace App\DTO;

use App\DTO\SmartPlaylistRule as Rule;
use Carbon\Carbon;
use Closure;
use Webmozart\Assert\Assert;

final class SmartPlaylistSqlElements
{
    private const DATE_MODELS = [
        Rule::MODEL_LAST_PLAYED,
        Rule::MODEL_DATE_ADDED,
        Rule::MODEL_DATE_MODIFIED,
    ];

    private const MODEL_COLUMN_REMAP = [
        Rule::MODEL_ALBUM_NAME => 'albums.name',
        Rule::MODEL_ARTIST_NAME => 'artists.name',
        Rule::MODEL_DATE_ADDED => 'songs.created_at',
        Rule::MODEL_DATE_MODIFIED => 'songs.updated_at',
    ];

    private const CLAUSE_WHERE = 'where';
    private const CLAUSE_WHERE_BETWEEN = 'whereBetween';
    private const CLAUSE_WHERE_NOT_BETWEEN = 'whereNotBetween';

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
        if (
            in_array($rule->model, self::DATE_MODELS, true) &&
            in_array($operator, [Rule::OPERATOR_IS, Rule::OPERATOR_IS_NOT], true)
        ) {
            $operator = $operator === Rule::OPERATOR_IS ? Rule::OPERATOR_IS_BETWEEN : Rule::OPERATOR_IS_NOT_BETWEEN;
            $nextDay = Carbon::createFromFormat('Y-m-d', $value[0])->addDay()->format('Y-m-d');
            $value = [$value[0], $nextDay];
        }

        $column = array_key_exists($rule->model, self::MODEL_COLUMN_REMAP)
            ? self::MODEL_COLUMN_REMAP[$rule->model]
            : $rule->model;

        $resolvers = [
            Rule::OPERATOR_BEGINS_WITH => [$column, 'LIKE', "$value[0]%"],
            Rule::OPERATOR_ENDS_WITH => [$column, 'LIKE', "%$value[0]"],
            Rule::OPERATOR_IS => [$column, '=', $value[0]],
            Rule::OPERATOR_IS_NOT => [$column, '<>', $value[0]],
            Rule::OPERATOR_CONTAINS => [$column, 'LIKE', "%$value[0]%"],
            Rule::OPERATOR_NOT_CONTAIN => [$column, 'NOT LIKE', "%$value[0]%"],
            Rule::OPERATOR_IS_LESS_THAN => [$column, '<', $value[0]],
            Rule::OPERATOR_IS_GREATER_THAN => [$column, '>', $value[0]],
            Rule::OPERATOR_IS_BETWEEN => [$column, $value],
            Rule::OPERATOR_IS_NOT_BETWEEN => [$column, $value],
            Rule::OPERATOR_NOT_IN_LAST => static fn () => [$column, '<', now()->subDays($value[0])],
            Rule::OPERATOR_IN_LAST => static fn () => [$column, '>=', now()->subDays($value[0])],
        ];

        Assert::keyExists($resolvers, $operator);

        $clause = match ($operator) {
            Rule::OPERATOR_IS_BETWEEN => self::CLAUSE_WHERE_BETWEEN,
            Rule::OPERATOR_IS_NOT_BETWEEN => self::CLAUSE_WHERE_NOT_BETWEEN,
            default => self::CLAUSE_WHERE,
        };

        $parameters = $resolvers[$operator] instanceof Closure ? $resolvers[$operator]() : $resolvers[$operator];

        return new self($clause, ...$parameters);
    }
}
