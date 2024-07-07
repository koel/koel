<?php

namespace App\Values;

use Illuminate\Support\Collection;

final class SmartPlaylistRuleGroupCollection extends Collection
{
    public static function create(array $array): self
    {
        return new self(
            collect($array)->transform(static function (array|SmartPlaylistRuleGroup $group): SmartPlaylistRuleGroup {
                return $group instanceof SmartPlaylistRuleGroup ? $group : SmartPlaylistRuleGroup::make($group);
            })
        );
    }
}
