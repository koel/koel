<?php

namespace App\Models;

use App\Helpers\Uuid;
use Carbon\Carbon;
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
 */
class PlaylistCollaborationToken extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(static function (PlaylistCollaborationToken $token): void {
            $token->token ??= Uuid::generate();
        });
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    protected function expired(): Attribute
    {
        return Attribute::get(fn (): bool => $this->created_at->addDays(7)->isPast())->shouldCache();
    }
}
