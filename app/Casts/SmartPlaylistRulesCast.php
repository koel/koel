<?php

namespace App\Casts;

use App\Values\SmartPlaylistRuleGroupCollection;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class SmartPlaylistRulesCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?SmartPlaylistRuleGroupCollection
    {
        if (!$value) {
            return null;
        }

        return attempt(static fn () => SmartPlaylistRuleGroupCollection::create(json_decode($value, true)));
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (is_array($value)) {
            $value = SmartPlaylistRuleGroupCollection::create($value);
        }

        return $value?->toJson() ?? null;
    }
}
