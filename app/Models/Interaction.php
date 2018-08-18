<?php

namespace App\Models;

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
}
