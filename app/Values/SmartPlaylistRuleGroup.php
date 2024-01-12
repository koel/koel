<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

final class SmartPlaylistRuleGroup implements Arrayable
{
    private function __construct(public string $id, public Collection $rules)
    {
        Assert::uuid($id);
    }

    public static function make(array $array): self
    {
        return new self(
            id: Arr::get($array, 'id'),
            rules: collect(Arr::get($array, 'rules', []))->transform(
                static function (array|SmartPlaylistRule $rule): SmartPlaylistRule {
                    return $rule instanceof SmartPlaylistRule ? $rule : SmartPlaylistRule::make($rule);
                }
            ),
        );
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
