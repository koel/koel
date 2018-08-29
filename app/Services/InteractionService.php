<?php

namespace App\Services;

use App\Events\SongLikeToggled;
use App\Models\Interaction;
use App\Models\User;

class InteractionService
{
    private $interaction;

    public function __construct(Interaction $interaction)
    {
        $this->interaction = $interaction;
    }

    /**
     * Increase the number of times a song is played by a user.
     *
     * @return Interaction The affected Interaction object
     */
    public function increasePlayCount(string $songId, User $user): Interaction
    {
        return tap($this->interaction->firstOrCreate([
            'song_id' => $songId,
            'user_id' => $user->id,
        ]), static function (Interaction $interaction): void {
            if (!$interaction->exists) {
                $interaction->liked = false;
            }

            $interaction->play_count++;
            $interaction->save();
        });
    }

    /**
     * Like or unlike a song on behalf of a user.
     *
     * @return Interaction The affected Interaction object.
     */
    public function toggleLike(string $songId, User $user): Interaction
    {
        return tap($this->interaction->firstOrCreate([
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
     * @param string[] $songIds
     *
     * @return Interaction[] The array of Interaction objects.
     */
    public function batchLike(array $songIds, User $user): array
    {
        return collect($songIds)->map(function ($songId) use ($user): Interaction {
            return tap($this->interaction->firstOrCreate([
                'song_id' => $songId,
                'user_id' => $user->id,
            ]), static function (Interaction $interaction): void {
                if (!$interaction->exists) {
                    $interaction->play_count = 0;
                }

                $interaction->liked = true;
                $interaction->save();

                event(new SongLikeToggled($interaction));
            });
        })->all();
    }

    /**
     * Unlike several songs at once.
     *
     * @param string[] $songIds
     */
    public function batchUnlike(array $songIds, User $user): void
    {
        $this->interaction
            ->whereIn('song_id', $songIds)
            ->where('user_id', $user->id)
            ->get()
            ->each(static function (Interaction $interaction): void {
                $interaction->liked = false;
                $interaction->save();

                event(new SongLikeToggled($interaction));
            }
        );
    }
}
