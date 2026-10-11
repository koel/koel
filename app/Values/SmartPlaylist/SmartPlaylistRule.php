<?php

namespace App\Values\SmartPlaylist;

use App\Enums\SmartPlaylistModel;
use App\Enums\SmartPlaylistOperator;
use App\Helpers\Uuid;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Webmozart\Assert\Assert;

final class SmartPlaylistRule implements Arrayable
{
    public string $id;
    public SmartPlaylistModel $model;
    public SmartPlaylistOperator $operator;
    public array $value;

    private function __construct(array $config)
    {
        self::assertConfig($config);

        $this->id = $config['id'] ?? Uuid::generate();
        $this->value = $config['value'];
        $this->model = SmartPlaylistModel::from($config['model']);
        $this->operator = SmartPlaylistOperator::from($config['operator']);
    }

    /** @noinspection PhpExpressionResultUnusedInspection */
    public static function assertConfig(array $config, bool $allowUserIdModel = true): void
    {
        if ($config['id'] ?? null) {
            Assert::uuid($config['id']);
        }

        $operator = SmartPlaylistOperator::from($config['operator']);

        if (!$allowUserIdModel) {
            Assert::false($config['model'] === SmartPlaylistModel::USER_ID);
        }

        $model = SmartPlaylistModel::from($config['model']);

        Assert::isArray($config['value']);
        Assert::count($config['value'], $operator->takesTwoValues() ? 2 : 1);

        foreach ($config['value'] as $value) {
            self::assertValue($model, $operator, $value);
        }
    }

    private static function assertValue(SmartPlaylistModel $model, SmartPlaylistOperator $operator, mixed $value): void
    {
        if ($operator->takesDayCount()) {
            Assert::integerish($value);
        } elseif ($model->isDate()) {
            Assert::true(Carbon::canBeCreatedFromFormat((string) $value, 'Y-m-d'));
        } elseif ($model->isNumeric()) {
            Assert::numeric($value);
        } else {
            Assert::string($value);
        }
    }

    public static function make(array $config): self
    {
        return new self($config);
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'model' => $this->model->value,
            'operator' => $this->operator->value,
            'value' => $this->value,
        ];
    }

    public function equals(array|self $rule): bool
    {
        if (is_array($rule)) {
            $rule = self::make($rule);
        }

        return (
            $this->operator === $rule->operator
            && !array_diff($this->value, $rule->value)
            && $this->model === $rule->model
        );
    }
}
