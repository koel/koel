<?php

namespace App\Casts;

use App\Values\SmartPlaylistRuleGroup;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;

class SmartPlaylistRulesCast implements CastsAttributes
{
    /** @return Collection|array<SmartPlaylistRuleGroup> */
    public function get($model, string $key, $value, array $attributes): Collection
    {
        return collect(json_decode($value, true) ?: [])->map(static function (array $group): ?SmartPlaylistRuleGroup {
            return SmartPlaylistRuleGroup::tryCreate($group);
        });
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        return json_encode($value ?: []);
    }
}
