<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use JsonSerializable;

final readonly class EqualizerPreset implements Arrayable, JsonSerializable
{
    /** @param array<int, float> $gains */
    private function __construct(
        public ?string $id,
        public ?string $name,
        public float $preamp,
        public array $gains,
    ) {}

    public static function tryFromArray(mixed $data): ?self
    {
        if (!is_array($data)) {
            return null;
        }

        $id = Arr::get($data, 'id');
        $name = Arr::get($data, 'name');
        $preamp = Arr::get($data, 'preamp');
        $gains = Arr::get($data, 'gains');

        if (
            $id !== null && (!is_string($id) || $id === '')
            || $name !== null && (!is_string($name) || trim($name) === '')
            || !is_numeric($preamp)
            || !is_array($gains)
            || count($gains) !== 10
        ) {
            return null;
        }

        foreach ($gains as $gain) {
            if (!is_numeric($gain)) {
                return null;
            }
        }

        return new self(
            id: $id,
            name: $name,
            preamp: (float) $preamp,
            gains: array_values(array_map(static fn ($gain) => (float) $gain, $gains)),
        );
    }

    public static function default(): self
    {
        return new self(id: null, name: 'Default', preamp: 0, gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
    }

    /** @param array<int, float|int> $gains */
    public static function make(string $name, float $preamp, array $gains, ?string $id = null): self
    {
        return new self(
            id: $id,
            name: $name,
            preamp: $preamp,
            gains: array_values(array_map(static fn (float|int $gain): float => (float) $gain, $gains)),
        );
    }

    public function withId(string $id): self
    {
        return new self(id: $id, name: $this->name, preamp: $this->preamp, gains: $this->gains);
    }

    /** @inheritDoc */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'preamp' => $this->preamp,
            'gains' => $this->gains,
        ];
    }

    /** @inheritDoc */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
