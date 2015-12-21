<?php

namespace App\Models;

use App\Events\SongLikeToggled;
use App\Traits\CanFilterByUser;
use Illuminate\Database\Eloquent\Model;

/**
 * @property bool liked
 * @property int play_count
 */
class Interaction extends Model
{
    use CanFilterByUser;

    protected $casts = [
        'liked' => 'boolean',
        'play_count' => 'integer',
    ];

    protected $guarded = ['id'];

    protected $hidden = ['user_id', 'created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
        $interaction = self::firstOrCreate([
            'song_id' => $songId,
            'user_id' => $user->id,
        ]);

        if (!$interaction->exists) {
            $interaction->liked = false;
        }

        ++$interaction->play_count;
        $interaction->save();

        return $interaction;
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
        $interaction = self::firstOrCreate([
            'song_id' => $songId,
            'user_id' => $user->id,
        ]);

        if (!$interaction->exists) {
            $interaction->play_count = 0;
        }

        $interaction->liked = !$interaction->liked;
        $interaction->save();

        event(new SongLikeToggled($interaction));

        return $interaction;
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
        $result = [];

        foreach ($songIds as $songId) {
            $interaction = self::firstOrCreate([
                'song_id' => $songId,
                'user_id' => $user->id,
            ]);

            if (!$interaction->exists) {
                $interaction->play_count = 0;
            }

            $interaction->liked = true;
            $interaction->save();

            event(new SongLikeToggled($interaction));

            $result[] = $interaction;
        }

        return $result;
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
        foreach (self::whereIn('song_id', $songIds)->whereUserId($user->id)->get() as $interaction) {
            $interaction->liked = false;
            $interaction->save();

            event(new SongLikeToggled($interaction));
        }
    }
}
