<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;
use Webmozart\Assert\Assert;

final class SmartPlaylistRule implements Arrayable
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

    public ?int $id;
    public string $operator;
    public array $value;
    public string $model;

    private function __construct(array $config)
    {
        Assert::oneOf($config['operator'], self::VALID_OPERATORS);

        $this->id = $config['id'] ?? null;
        $this->value = $config['value'];
        $this->model = $config['model'];
        $this->operator = $config['operator'];
    }

    public static function create(array $config): self
    {
        return new static($config);
    }

    /** @return array<mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'model' => $this->model,
            'operator' => $this->operator,
            'value' => $this->value,
        ];
    }

    /** @param array|self $rule */
    public function equals($rule): bool
    {
        if (is_array($rule)) {
            $rule = self::create($rule);
        }

        return $this->operator === $rule->operator
            && !array_diff($this->value, $rule->value)
            && $this->model === $rule->model;
    }
}
