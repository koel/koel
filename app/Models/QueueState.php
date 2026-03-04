<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string> $song_ids
 * @property ?string $current_song_id
 * @property int $playback_position
 * @property User $user
 */
class QueueState extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'song_ids' => 'array',
        'playback_position' => 'int',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
