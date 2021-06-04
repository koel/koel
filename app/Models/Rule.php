<?php

namespace App\Models;

use App\Factories\SmartPlaylistRuleParameterFactory;
use Illuminate\Database\Eloquent\Builder;
use Webmozart\Assert\Assert;

class Rule
{
    public const OPERATOR_IS = 'is';
    public const OPERATOR_IS_NOT = 'isNot';
    public const OPERATOR_CONTAINS = 'contains';
    public const OPERATOR_NOT_CONTAIN = 'notContain';
    public const OPERATOR_IS_BETWEEN = 'isBetween';
    public const OPERATOR_IS_GREATER_THAN = 'isGreaterThan';
    public const OPERATOR_IS_LESS_THAN = 'isLessThan';
    public const OPERATOR_BEGINS_WITH = 'beginsWith';
    public const OPERATOR_ENDS_WITH = 'endsWith';
    public const OPERATOR_IN_LAST = 'inLast';
    public const OPERATOR_NOT_IN_LAST = 'notInLast';

    public const VALID_OPERATORS = [
        self::OPERATOR_BEGINS_WITH,
        self::OPERATOR_CONTAINS,
        self::OPERATOR_ENDS_WITH,
        self::OPERATOR_IN_LAST,
        self::OPERATOR_IS,
        self::OPERATOR_IS_BETWEEN,
        self::OPERATOR_IS_GREATER_THAN,
        self::OPERATOR_IS_LESS_THAN,
        self::OPERATOR_IS_NOT,
        self::OPERATOR_NOT_CONTAIN,
        self::OPERATOR_NOT_IN_LAST,
    ];

    private $operator;
    private $value;
    private $model;
    private SmartPlaylistRuleParameterFactory $parameterFactory;

    private function __construct(array $config)
    {
        Assert::oneOf($config['operator'], self::VALID_OPERATORS);

        $this->value = $config['value'];
        $this->model = $config['model'];
        $this->operator = $config['operator'];

        $this->parameterFactory = new SmartPlaylistRuleParameterFactory();
    }

    public static function create(array $config): self
    {
        return new static($config);
    }

    public function build(Builder $query, ?string $model = null): Builder
    {
        if (!$model) {
            $model = $this->model;
        }

        $fragments = explode('.', $model, 2);

        if (count($fragments) === 1) {
            return $query->{$this->resolveLogic()}(
                ...$this->parameterFactory->createParameters($model, $this->operator, $this->value)
            );
        }

        // If the model is something like 'artist.name' or 'interactions.play_count', we have a subquery to deal with.
        // We handle such a case with a recursive call which, in theory, should work with an unlimited level of nesting,
        // though in practice we only have one level max.
        return $query->whereHas($fragments[0], fn (Builder $subQuery) => $this->build($subQuery, $fragments[1]));
    }

    /**
     * Resolve the logic of a (sub)query base on the configured operator.
     * Basically, if the operator is "between," we use "whereBetween." Otherwise, it's "where." Simple.
     */
    private function resolveLogic(): string
    {
        return $this->operator === self::OPERATOR_IS_BETWEEN ? 'whereBetween' : 'where';
    }
}
