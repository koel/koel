<?php

namespace App\Models;

use Database\Factories\QueueStateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string> $song_ids
 * @property ?string $current_song_id
 * @property int $playback_position
 * @property User $user
 *
 * @method static QueueStateFactory factory(...$parameters)
 */
class QueueState extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'song_ids' => 'array',
            'playback_position' => 'int',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
