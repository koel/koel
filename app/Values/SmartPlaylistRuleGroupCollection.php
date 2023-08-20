<?php

namespace App\Values;

use Illuminate\Support\Collection;

final class SmartPlaylistRuleGroupCollection extends Collection
{
    public static function create(array $array): self
    {
        return new self(collect($array)->transform([SmartPlaylistRuleGroup::class, 'create']));
    }
}
