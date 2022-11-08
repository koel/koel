<?php

namespace App\Services;

use App\Exceptions\NonSmartPlaylistException;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Values\SmartPlaylistRule as Rule;
use App\Values\SmartPlaylistRuleGroup as RuleGroup;
use App\Values\SmartPlaylistSqlElements as SqlElements;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SmartPlaylistService
{
    /** @return Collection|array<array-key, Song> */
    public function getSongs(Playlist $playlist, ?User $user = null): Collection
    {
        throw_unless($playlist->is_smart, NonSmartPlaylistException::create($playlist));

        $query = Song::query()->withMeta($user ?? $playlist->user);

        $playlist->rule_groups->each(static function (RuleGroup $group, int $index) use ($query): void {
            $clause = $index === 0 ? 'where' : 'orWhere';

            $query->$clause(static function (Builder $subQuery) use ($group): void {
                $group->rules->each(static function (Rule $rule) use ($subQuery): void {
                    $tokens = SqlElements::fromRule($rule);
                    $subQuery->{$tokens->clause}(...$tokens->parameters);
                });
            });
        });

        return $query->orderBy('songs.title')->get();
    }
}
