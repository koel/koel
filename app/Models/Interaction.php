<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property bool $liked
 * @property int $play_count
 * @property Song $song
 * @property User $user
 * @property int $id
 * @property string $song_id
 * @property Carbon|string $last_played_at
 */
class Interaction extends Model
{
    use HasFactory;

    protected $casts = [
        'liked' => 'boolean',
        'play_count' => 'integer',
    ];

    protected $guarded = ['id'];
    protected $hidden = ['id', 'user_id', 'created_at', 'updated_at', 'last_played_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}
