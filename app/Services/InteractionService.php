<?php

namespace App\Services;

use App\Events\SongLikeToggled;
use App\Models\Interaction;
use App\Models\User;
use Illuminate\Support\Collection;

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
     * @param string $songId
     * @param User   $user
     *
     * @return Interaction The affected Interaction object
     */
    public function increasePlayCount($songId, User $user)
    {
        return tap($this->interaction->firstOrCreate([
            'song_id' => $songId,
            'user_id' => $user->id,
        ]), static function (Interaction $interaction) {
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
     * @param string $songId
     * @param User   $user
     *
     * @return Interaction The affected Interaction object.
     */
    public function toggleLike($songId, User $user)
    {
        return tap($this->interaction->firstOrCreate([
            'song_id' => $songId,
            'user_id' => $user->id,
        ]), static function (Interaction $interaction) {
            $interaction->liked = !$interaction->liked;
            $interaction->save();

            event(new SongLikeToggled($interaction));
        });
    }

    /**
     * Like several songs at once as a user.
     *
     * @param array $songIds
     * @param User  $user
     *
     * @return array The array of Interaction objects.
     */
    public function batchLike(array $songIds, User $user)
    {
        return collect($songIds)->map(function ($songId) use ($user) {
            return tap($this->interaction->firstOrCreate([
                'song_id' => $songId,
                'user_id' => $user->id,
            ]), static function (Interaction $interaction) {
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
     * @param array $songIds
     * @param User  $user
     */
    public function batchUnlike(array $songIds, User $user)
    {
        $this->interaction
            ->whereIn('song_id', $songIds)
            ->where('user_id', $user->id)
            ->get()
            ->each(static function (Interaction $interaction) {
                $interaction->liked = false;
                $interaction->save();

                event(new SongLikeToggled($interaction));
            }
        );
    }

    /**
     * Get all songs favorited by a user.
     *
     * @param User $user
     *
     * @return Collection
     */
    public function getUserFavorites(User $user)
    {
        return $this->interaction->where([
            'user_id' => $user->id,
            'like' => true
        ])
            ->with('song')
            ->get()
            ->pluck('song');
    }
}
