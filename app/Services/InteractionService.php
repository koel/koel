<?php

namespace App\Services;

use App\Events\MultipleSongsLiked;
use App\Events\MultipleSongsUnliked;
use App\Events\SongLikeToggled;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Collection;

class InteractionService
{
    public function increasePlayCount(Song $song, User $user): Interaction
    {
        return tap(Interaction::query()->firstOrCreate([
            'song_id' => $song->id,
            'user_id' => $user->id,
        ]), static function (Interaction $interaction): void {
            if (!$interaction->exists) {
                $interaction->liked = false;
            }

            $interaction->last_played_at = now();

            ++$interaction->play_count;
            $interaction->save();
        });
    }

    /**
     * Like or unlike a song as a user.
     *
     * @return Interaction The affected Interaction object
     */
    public function toggleLike(Song $song, User $user): Interaction
    {
        return tap(Interaction::query()->firstOrCreate([
            'song_id' => $song->id,
            'user_id' => $user->id,
        ]), static function (Interaction $interaction): void {
            $interaction->liked = !$interaction->liked;
            $interaction->save();

            event(new SongLikeToggled($interaction));
        });
    }

    /**
     * Like several songs at once as a user.
     *
     * @param array<array-key, Song>|Collection $songs
     *
     * @return array<Interaction>|Collection The array of Interaction objects
     */
    public function likeMany(Collection $songs, User $user): Collection
    {
        $interactions = $songs->map(static function (Song $song) use ($user): Interaction {
            return tap(Interaction::query()->firstOrCreate([
                'song_id' => $song->id,
                'user_id' => $user->id,
            ]), static function (Interaction $interaction): void {
                $interaction->play_count ??= 0;
                $interaction->liked = true;
                $interaction->save();
            });
        });

        event(new MultipleSongsLiked($songs, $user));

        return $interactions;
    }

    /**
     * Unlike several songs at once.
     *
     * @param array<array-key, Song>|Collection $songs
     */
    public function unlikeMany(Collection $songs, User $user): void
    {
        Interaction::query()
            ->whereIn('song_id', $songs->pluck('id')->all())
            ->where('user_id', $user->id)
            ->update(['liked' => false]);

        event(new MultipleSongsUnliked($songs, $user));
    }
}
