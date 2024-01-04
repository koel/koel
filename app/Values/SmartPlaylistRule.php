<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
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
    public const OPERATOR_IS_NOT_BETWEEN = 'isNotBetween';

    public const VALID_OPERATORS = [
        self::OPERATOR_BEGINS_WITH,
        self::OPERATOR_CONTAINS,
        self::OPERATOR_ENDS_WITH,
        self::OPERATOR_IN_LAST,
        self::OPERATOR_IS,
        self::OPERATOR_IS_BETWEEN,
        self::OPERATOR_IS_NOT_BETWEEN,
        self::OPERATOR_IS_GREATER_THAN,
        self::OPERATOR_IS_LESS_THAN,
        self::OPERATOR_IS_NOT,
        self::OPERATOR_NOT_CONTAIN,
        self::OPERATOR_NOT_IN_LAST,
    ];

    private const MODEL_TITLE = 'title';
    public const MODEL_ALBUM_NAME = 'album.name';
    public const MODEL_ARTIST_NAME = 'artist.name';
    private const MODEL_PLAY_COUNT = 'interactions.play_count';
    public const MODEL_LAST_PLAYED = 'interactions.last_played_at';
    private const MODEL_USER_ID = 'interactions.user_id';
    private const MODEL_LENGTH = 'length';
    public const MODEL_DATE_ADDED = 'created_at';
    public const MODEL_DATE_MODIFIED = 'updated_at';
    private const MODEL_GENRE = 'genre';
    private const MODEL_YEAR = 'year';

    private const VALID_MODELS = [
        self::MODEL_TITLE,
        self::MODEL_ALBUM_NAME,
        self::MODEL_ARTIST_NAME,
        self::MODEL_PLAY_COUNT,
        self::MODEL_LAST_PLAYED,
        self::MODEL_LENGTH,
        self::MODEL_DATE_ADDED,
        self::MODEL_DATE_MODIFIED,
        self::MODEL_GENRE,
        self::MODEL_YEAR,
    ];

    public string $id;
    public string $operator;
    public array $value;
    public string $model;

    private function __construct(array $config)
    {
        self::assertConfig($config);

        $this->id = $config['id'] ?? Str::uuid()->toString();
        $this->value = $config['value'];
        $this->model = $config['model'];
        $this->operator = $config['operator'];
    }

    public static function assertConfig(array $config, bool $allowUserIdModel = true): void
    {
        if ($config['id'] ?? null) {
            Assert::uuid($config['id']);
        }

        Assert::oneOf($config['operator'], self::VALID_OPERATORS);
        Assert::oneOf(
            $config['model'],
            $allowUserIdModel ? array_prepend(self::VALID_MODELS, self::MODEL_USER_ID) : self::VALID_MODELS
        );
        Assert::isArray($config['value']);
        Assert::countBetween($config['value'], 1, 2);
    }

    public static function make(array $config): self
    {
        return new self($config);
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

    public function equals(array|self $rule): bool
    {
        if (is_array($rule)) {
            $rule = self::make($rule);
        }

        return $this->operator === $rule->operator
            && !array_diff($this->value, $rule->value)
            && $this->model === $rule->model;
    }
}
