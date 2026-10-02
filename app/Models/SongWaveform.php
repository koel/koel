<?php

namespace App\Models;

use Database\Factories\SongWaveformFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $song_id
 * @property Song $song
 * @property array<float> $levels The RMS level of each evenly sized slice of the song, between 0 and 1
 *
 * @method static SongWaveformFactory factory(...$parameters)
 */
#[Table(key: 'song_id', keyType: 'string', incrementing: false)]
#[Unguarded]
class SongWaveform extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'levels' => 'array',
        ];
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}
