<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\CanFilterByUser;
use DB;

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
     * @param int|null $userId
     *
     * @return Interaction
     */
    public static function increasePlayCount($songId, $userId = null)
    {
        $interaction = self::firstOrCreate([
            'song_id' => $songId,
            'user_id' => $userId ?: auth()->user()->id,
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
     * @param int|null $userId
     *
     * @return Interaction
     */
    public static function toggleLike($songId, $userId = null)
    {
        $interaction = self::firstOrCreate([
            'song_id' => $songId,
            'user_id' => $userId ?: auth()->user()->id,
        ]);

        if (!$interaction->exists) {
            $interaction->play_count = 0;
        }

        $interaction->liked = !$interaction->liked;
        $interaction->save();

        return $interaction;
    }

    /**
     * Like several songs at once.
     *
     * @param array $songIds
     * @param int|null  $userId
     *
     * @return array
     */
    public static function batchLike(array $songIds, $userId = null)
    {
        $result = [];

        foreach ($songIds as $songId) {
            $interaction = self::firstOrCreate([
                'song_id' => $songId,
                'user_id' => $userId ?: auth()->user()->id,
            ]);

            if (!$interaction->exists) {
                $interaction->play_count = 0;
            }

            $interaction->liked = true;
            $interaction->save();

            $result[] = $interaction;
        }

        return $result;
    }

    /**
     * Unlike several songs at once.
     *
     * @param array $songIds
     * @param int|null  $userId
     *
     * @return int
     */
    public static function batchUnlike(array $songIds, $userId = null)
    {
        return DB::table('interactions')
            ->whereIn('song_id', $songIds)
            ->where('user_id', $userId ?: auth()->user()->id)
            ->update(['liked' => false]);
    }
}
