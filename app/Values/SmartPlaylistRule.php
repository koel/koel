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

    private const MODEL_TITLE = 'title';
    private const MODEL_ALBUM_NAME = 'album.name';
    private const MODEL_ARTIST_NAME = 'artist.name';
    private const MODEL_PLAY_COUNT = 'interactions.play_count';
    private const MODEL_LAST_PLAYED = 'interactions.updated_at';
    private const MODEL_USER_ID = 'interactions.user_id';
    private const MODEL_LENGTH = 'length';
    private const MODEL_DATE_ADDED = 'created_at';
    private const MODEL_DATE_MODIFIED = 'updated_at';

    private const VALID_MODELS = [
        self::MODEL_TITLE,
        self::MODEL_ALBUM_NAME,
        self::MODEL_ARTIST_NAME,
        self::MODEL_PLAY_COUNT,
        self::MODEL_LAST_PLAYED,
        self::MODEL_LENGTH,
        self::MODEL_DATE_ADDED,
        self::MODEL_DATE_MODIFIED,
    ];

    private const MODEL_COLUMN_MAP = [
        self::MODEL_TITLE => 'songs.title',
        self::MODEL_ALBUM_NAME => 'albums.name',
        self::MODEL_ARTIST_NAME => 'artists.name',
        self::MODEL_LENGTH => 'songs.length',
        self::MODEL_DATE_ADDED => 'songs.created_at',
        self::MODEL_DATE_MODIFIED => 'songs.updated_at',
    ];

    public ?int $id;
    public string $operator;
    public array $value;
    public string $model;

    private function __construct(array $config)
    {
        self::assertConfig($config);

        $this->id = $config['id'] ?? null;
        $this->value = $config['value'];
        $this->model = $config['model'];
        $this->operator = $config['operator'];
    }

    public static function assertConfig(array $config, bool $allowUserIdModel = true): void
    {
        Assert::oneOf($config['operator'], self::VALID_OPERATORS);
        Assert::oneOf(
            $config['model'],
            $allowUserIdModel ? array_prepend(self::VALID_MODELS, self::MODEL_USER_ID) : self::VALID_MODELS
        );
        Assert::isArray($config['value']);
        Assert::countBetween($config['value'], 1, 2);
    }

    public static function create(array $config): self
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
            $rule = self::create($rule);
        }

        return $this->operator === $rule->operator
            && !array_diff($this->value, $rule->value)
            && $this->model === $rule->model;
    }

    /** @return array<mixed> */
    public function toCriteriaParameters(): array
    {
        $column = array_key_exists($this->model, self::MODEL_COLUMN_MAP)
            ? self::MODEL_COLUMN_MAP[$this->model]
            : $this->model;

        $resolvers = [
            self::OPERATOR_BEGINS_WITH => [$column, 'LIKE', "{$this->value[0]}%"],
            self::OPERATOR_ENDS_WITH => [$column, 'LIKE', "%{$this->value[0]}"],
            self::OPERATOR_IS => [$column, '=', $this->value[0]],
            self::OPERATOR_IS_NOT => [$column, '<>', $this->value[0]],
            self::OPERATOR_CONTAINS => [$column, 'LIKE', "%{$this->value[0]}%"],
            self::OPERATOR_NOT_CONTAIN => [$column, 'NOT LIKE', "%{$this->value[0]}%"],
            self::OPERATOR_IS_LESS_THAN => [$column, '<', $this->value[0]],
            self::OPERATOR_IS_GREATER_THAN => [$column, '>', $this->value[0]],
            self::OPERATOR_IS_BETWEEN => [$column, $this->value],
            self::OPERATOR_NOT_IN_LAST => fn (): array => [$column, '<', now()->subDays($this->value[0])],
            self::OPERATOR_IN_LAST => fn (): array => [$column, '>=', now()->subDays($this->value[0])],
        ];

        Assert::keyExists($resolvers, $this->operator);

        return is_callable($resolvers[$this->operator]) ? $resolvers[$this->operator]() : $resolvers[$this->operator];
    }
}
