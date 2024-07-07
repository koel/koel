<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Throwable;

final class Equalizer implements Arrayable
{
    /** @param array<int>|null $gains */
    private function __construct(public ?string $name, public ?int $preamp, public ?array $gains)
    {
    }

    public static function tryMake(array|string $data): self
    {
        try {
            if (is_string($data)) {
                $data = ['name' => $data];
            }

            return new self(Arr::get($data, 'name') ?? null, Arr::get($data, 'preamp'), Arr::get($data, 'gains'));
        } catch (Throwable) {
            return self::default();
        }
    }

    public static function default(): self
    {
        return new self(name: 'Default', preamp: 0, gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
    }

    /** @return array<mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'preamp' => $this->preamp,
            'gains' => $this->gains,
        ];
    }
}
