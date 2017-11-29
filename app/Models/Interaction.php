<?php

namespace App\Models;

use App\Events\SongLikeToggled;
use App\Traits\CanFilterByUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property bool  liked
 * @property int   play_count
 * @property Song  song
 * @property User  user
 * @property int id
 */
class Interaction extends Model
{
    use CanFilterByUser;

    protected $casts = [
        'liked' => 'boolean',
        'play_count' => 'integer',
    ];

    protected $guarded = ['id'];

    protected $hidden = ['id', 'user_id', 'created_at', 'updated_at'];

    /**
     * An interaction belongs to a user.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * An interaction is associated with a song.
     *
     * @return BelongsTo
     */
    public function song()
    {
        return $this->belongsTo(Song::class);
    }

    /**
     * Increase the number of times a song is played by a user.
     *
     * @param string $songId
     * @param User   $user
     *
     * @return Interaction
     */
    public static function increasePlayCount($songId, User $user)
    {
        return tap(self::firstOrCreate([
            'song_id' => $songId,
            'user_id' => $user->id,
        ]), function (Interaction $interaction) {
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
     * @return Interaction
     */
    public static function toggleLike($songId, User $user)
    {
        return tap(self::firstOrCreate([
            'song_id' => $songId,
            'user_id' => $user->id,
        ]), function (Interaction $interaction) {
            $interaction->liked = !$interaction->liked;
            $interaction->save();

            event(new SongLikeToggled($interaction));
        });
    }

    /**
     * Like several songs at once.
     *
     * @param array $songIds
     * @param User  $user
     *
     * @return array
     */
    public static function batchLike(array $songIds, User $user)
    {
        return collect($songIds)->map(function ($songId) use ($user) {
            return tap(self::firstOrCreate([
                'song_id' => $songId,
                'user_id' => $user->id,
            ]), function (Interaction $interaction) {
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
     *
     * @return int
     */
    public static function batchUnlike(array $songIds, User $user)
    {
        self::whereIn('song_id', $songIds)->whereUserId($user->id)->get()->each(function (Interaction $interaction) {
            $interaction->liked = false;
            $interaction->save();

            event(new SongLikeToggled($interaction));
        });
    }
}
