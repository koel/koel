<?php

namespace App\Values\SmartPlaylist;

use App\Enums\SmartPlaylistModel;
use App\Enums\SmartPlaylistOperator;
use App\Helpers\Uuid;
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

        SmartPlaylistOperator::from($config['operator']);

        if (!$allowUserIdModel) {
            Assert::false($config['model'] === SmartPlaylistModel::USER_ID);
        }

        SmartPlaylistModel::from($config['model']);

        Assert::isArray($config['value']);
        Assert::countBetween($config['value'], 1, 2);
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

        return $this->operator === $rule->operator
            && !array_diff($this->value, $rule->value)
            && $this->model === $rule->model;
    }
}
