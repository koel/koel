<?php

namespace App\Services;

use App\Builders\SongBuilder;
use App\Enums\PlayableType;
use App\Exceptions\NonSmartPlaylistException;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Values\SmartPlaylist\SmartPlaylistQueryModifier as QueryModifier;
use App\Values\SmartPlaylist\SmartPlaylistRule as Rule;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroup as RuleGroup;
use Illuminate\Database\Eloquent\Collection;

class SmartPlaylistService
{
    /** @return Collection|array<array-key, Song> */
    public function getSongs(Playlist $playlist, ?User $user = null): Collection
    {
        throw_unless($playlist->is_smart, NonSmartPlaylistException::create($playlist));

        $user ??= $playlist->owner;

        $query = Song::query(type: PlayableType::SONG, user: $user)
            ->withMetaData()
            ->accessible();

        $playlist->rule_groups->each(static function (RuleGroup $group, int $index) use ($query): void {
            $whereClosure = static function (SongBuilder $subQuery) use ($group): void {
                $group->rules->each(static function (Rule $rule) use ($subQuery): void {
                    QueryModifier::applyRule($rule, $subQuery);
                });
            };

            $query->when(
                $index === 0,
                static fn (SongBuilder $query) => $query->where($whereClosure),
                static fn (SongBuilder $query) => $query->orWhere($whereClosure)
            );
        });

        return $query->orderBy('songs.title')->get();
    }
}
