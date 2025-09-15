<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property Carbon $created_at
 * @property Song|Album|Artist|Playlist $embeddable
 * @property User $user
 * @property string $id
 * @property string $embeddable_id
 * @property string $embeddable_type
 */
class Embed extends Model
{
    use HasFactory;
    use HasUlids;

    protected $guarded = [];
    protected $with = ['user', 'embeddable'];

    public function embeddable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
