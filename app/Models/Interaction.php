<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property bool $liked
 * @property int $play_count
 * @property Song $song
 * @property User $user
 * @property int $id
 *
 * @method static self firstOrCreate(array $where, array $params = [])
 * @method static self find(int $id)
 * @method static Builder whereSongIdAndUserId(string $songId, string $userId)
 * @method static Builder whereIn(...$params)
 */
class Interaction extends Model
{
    use HasFactory;

    protected $casts = [
        'liked' => 'boolean',
        'play_count' => 'integer',
    ];

    protected $guarded = ['id'];
    protected $hidden = ['id', 'user_id', 'created_at', 'updated_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}
