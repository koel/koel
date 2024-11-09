<?php

namespace App\Services;

use App\Events\MultipleSongsLiked;
use App\Events\MultipleSongsUnliked;
use App\Events\SongLikeToggled;
use App\Models\Interaction;
use App\Models\Song as Playable;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class InteractionService
{
    public function increasePlayCount(Playable $playable, User $user): Interaction
    {
        return tap(Interaction::query()->firstOrCreate([
            'song_id' => $playable->id,
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
     * Like or unlike a song/episode as a user.
     *
     * @return Interaction The affected Interaction object
     */
    public function toggleLike(Playable $playable, User $user): Interaction
    {
        return tap(Interaction::query()->firstOrCreate([
            'song_id' => $playable->id,
            'user_id' => $user->id,
        ]), static function (Interaction $interaction): void {
            $interaction->liked = !$interaction->liked;
            $interaction->save();

            event(new SongLikeToggled($interaction));
        });
    }

    /**
     * Like several songs/episodes at once as a user.
     *
     * @param Collection<array-key, Playable> $playables
     *
     * @return Collection<array-key, Interaction> The array of Interaction objects
     */
    public function likeMany(Collection $playables, User $user): Collection
    {
        $interactions = $playables->map(static function (Playable $playable) use ($user): Interaction {
            $interaction = Interaction::query()->whereBelongsTo($playable)->whereBelongsTo($user)->first();

            if ($interaction) {
                $interaction->update(['liked' => true]);
            } else {
                $interaction = Interaction::query()->create([
                    'song_id' => $playable->id,
                    'user_id' => $user->id,
                    'play_count' => 0,
                    'liked' => true,
                ]);
            }

            return $interaction;
        });

        event(new MultipleSongsLiked($playables, $user));

        return $interactions;
    }

    /**
     * Unlike several songs/episodes at once.
     *
     * @param array<array-key, Playable>|Collection $playables
     */
    public function unlikeMany(Collection $playables, User $user): void
    {
        Interaction::query()
            ->whereBelongsTo($playables)
            ->whereBelongsTo($user)
            ->update(['liked' => false]);

        event(new MultipleSongsUnliked($playables, $user));
    }
}
