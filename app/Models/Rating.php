<?php

namespace App\Models;

use App\Models\Contracts\Rateable;
use Carbon\Carbon;
use Database\Factories\RatingFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Rateable $rateable
 * @property User $user
 * @property int $id
 * @property string $rateable_id
 * @property string $rateable_type
 * @property int $rating
 *
 * @method static RatingFactory factory(...$parameters)
 */
#[Unguarded]
class Rating extends Model
{
    use HasFactory;

    public function rateable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
