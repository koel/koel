<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Throwable;

final class SmartPlaylistRuleGroup implements Arrayable
{
    public ?int $id;

    /** @var Collection|array<SmartPlaylistRule> */
    public Collection $rules;

    public static function create(array $jsonArray): ?self
    {
        $group = new self();

        try {
            $group->id = $jsonArray['id'] ?? null;

            $group->rules = collect(array_map(static function (array $rawRuleConfig) {
                return SmartPlaylistRule::create($rawRuleConfig);
            }, $jsonArray['rules']));

            return $group;
        } catch (Throwable $exception) {
            return null;
        }
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
