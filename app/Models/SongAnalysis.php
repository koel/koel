<?php

namespace App\Models;

use Database\Factories\SongAnalysisFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $song_id
 * @property Song $song
 * @property float $loudness The integrated loudness in LUFS
 * @property float $true_peak The true peak in dBTP
 * @property array<float> $waveform The RMS level of each evenly sized slice of the song, between 0 and 1
 *
 * @method static SongAnalysisFactory factory(...$parameters)
 */
#[Table(name: 'song_analyses', key: 'song_id', keyType: 'string', incrementing: false)]
#[Unguarded]
class SongAnalysis extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'loudness' => 'float',
            'true_peak' => 'float',
            'waveform' => 'array',
        ];
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}
