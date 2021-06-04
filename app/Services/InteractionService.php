<?php

namespace App\Services;

use App\Events\SongLikeToggled;
use App\Events\SongsBatchLiked;
use App\Events\SongsBatchUnliked;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Collection;

class InteractionService
{
    /**
     * Increase the number of times a song is played by a user.
     *
     * @return Interaction The affected Interaction object
     */
    public function increasePlayCount(string $songId, User $user): Interaction
    {
        return tap(Interaction::firstOrCreate([
            'song_id' => $songId,
            'user_id' => $user->id,
        ]), static function (Interaction $interaction): void {
            if (!$interaction->exists) {
                $interaction->liked = false;
            }

            ++$interaction->play_count;
            $interaction->save();
        });
    }

    /**
     * Like or unlike a song on behalf of a user.
     *
     * @return Interaction the affected Interaction object
     */
    public function toggleLike(string $songId, User $user): Interaction
    {
        return tap(Interaction::firstOrCreate([
            'song_id' => $songId,
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
     * @param array<string> $songIds
     *
     * @return array<Interaction>|Collection The array of Interaction objects
     */
    public function batchLike(array $songIds, User $user): Collection
    {
        $interactions = collect($songIds)->map(static fn ($songId): Interaction => tap(Interaction::firstOrCreate([
            'song_id' => $songId,
            'user_id' => $user->id,
        ]), static function (Interaction $interaction): void {
            if (!$interaction->exists) {
                $interaction->play_count = 0;
            }

            $interaction->liked = true;
            $interaction->save();
        }));

        event(new SongsBatchLiked($interactions->map(static function (Interaction $interaction): Song {
            return $interaction->song;
        }), $user));

        return $interactions;
    }

    /**
     * Unlike several songs at once.
     *
     * @param array<string> $songIds
     */
    public function batchUnlike(array $songIds, User $user): void
    {
        Interaction::whereIn('song_id', $songIds)
            ->where('user_id', $user->id)
            ->update(['liked' => false]);

        event(new SongsBatchUnliked(Song::find($songIds), $user));
    }
}
