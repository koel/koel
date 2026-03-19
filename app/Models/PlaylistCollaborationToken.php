<?php

namespace App\Models;

use App\Observers\PlaylistCollaborationTokenObserver;
use Carbon\Carbon;
use Database\Factories\PlaylistCollaborationTokenFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $token
 * @property Carbon $created_at
 * @property-read bool $expired
 * @property string $playlist_id
 * @property Playlist $playlist
 *
 * @method static PlaylistCollaborationTokenFactory factory(...$parameters)
 */
#[ObservedBy(PlaylistCollaborationTokenObserver::class)]
class PlaylistCollaborationToken extends Model
{
    use HasFactory;

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    protected function expired(): Attribute
    {
        return Attribute::get(fn (): bool => $this->created_at->addDays(7)->isPast())->shouldCache();
    }
}
