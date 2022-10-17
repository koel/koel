<?php

namespace App\Services;

use App\Exceptions\NonSmartPlaylistException;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Values\SmartPlaylistRule;
use App\Values\SmartPlaylistRuleGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SmartPlaylistService
{
    /** @return Collection|array<array-key, Song> */
    public function getSongs(Playlist $playlist, ?User $user = null): Collection
    {
        throw_unless($playlist->is_smart, NonSmartPlaylistException::create($playlist));

        $query = Song::query()->withMeta($user ?? $playlist->user);

        $playlist->rule_groups->each(static function (SmartPlaylistRuleGroup $group, int $index) use ($query): void {
            $clause = $index === 0 ? 'where' : 'orWhere';

            $query->$clause(static function (Builder $subQuery) use ($group): void {
                $group->rules->each(static function (SmartPlaylistRule $rule) use ($subQuery): void {
                    $subWhere = $rule->operator === SmartPlaylistRule::OPERATOR_IS_BETWEEN ? 'whereBetween' : 'where';
                    $subQuery->$subWhere(...$rule->toCriteriaParameters());
                });
            });
        });

        return $query->orderBy('songs.title')->get();
    }
}
