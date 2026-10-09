<?php

namespace App\Models;

use Database\Factories\PlayFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $song_id
 * @property Carbon $played_at
 * @property User $user
 * @property Song $song
 *
 * @method static PlayFactory factory(...$parameters)
 */
#[Unguarded]
#[WithoutTimestamps]
class Play extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}
