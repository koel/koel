<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final class SmartPlaylistRuleGroup implements Arrayable
{
    private function __construct(public ?int $id, public Collection $rules)
    {
    }

    public static function tryCreate(array $jsonArray): ?self
    {
        return attempt(static fn () => self::create($jsonArray));
    }

    public static function create(array $jsonArray): self
    {
        $rules = collect(array_map(static function (array $rawRuleConfig) {
            return SmartPlaylistRule::create($rawRuleConfig);
        }, $jsonArray['rules']));

        return new self(Arr::get($jsonArray, 'id'), $rules);
    }

    /** @return array<mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'rules' => $this->rules->toArray(),
        ];
    }
}
