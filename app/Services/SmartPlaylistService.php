<?php

namespace App\Services;

use App\Builders\SongBuilder;
use App\Enums\PlayableType;
use App\Exceptions\NonSmartPlaylistException;
use App\Facades\License;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Values\SmartPlaylist\SmartPlaylistRule as Rule;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroup as RuleGroup;
use App\Values\SmartPlaylist\SmartPlaylistSqlElements as SqlElements;
use Illuminate\Database\Eloquent\Collection;

class SmartPlaylistService
{
    /** @return Collection|array<array-key, Song> */
    public function getSongs(Playlist $playlist, ?User $user = null): Collection
    {
        $isPlus = once(static fn () => License::isPlus());

        throw_unless($playlist->is_smart, NonSmartPlaylistException::create($playlist));

        $user ??= $playlist->owner;

        $query = Song::query(type: PlayableType::SONG, user: $user)
            ->withMeta()
            ->when($isPlus, static fn (SongBuilder $query) => $query->accessible())
            ->when(
                $playlist->own_songs_only && License::isPlus(),
                static fn (SongBuilder $query) => $query->where('songs.owner_id', $user->id)
            );

        $playlist->rule_groups->each(static function (RuleGroup $group, int $index) use ($query): void {
            $whereClosure = static function (SongBuilder $subQuery) use ($group): void {
                $group->rules->each(static function (Rule $rule) use ($subQuery): void {
                    $tokens = SqlElements::fromRule($rule);
                    $subQuery->{$tokens->clause}(...$tokens->parameters);
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
