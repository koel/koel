<?php

namespace App\Models;

use App\Models\Contracts\Favoriteable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property Carbon $created_at
 * @property Favoriteable $favoriteable
 * @property User $user
 * @property int $id
 * @property string $favoriteable_id
 * @property string $favoriteable_type
 */
class Favorite extends Model
{
    use HasFactory;

    protected $guarded = [];
    public $timestamps = false;
    protected $with = ['user', 'favoriteable'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public static function booted(): void
    {
        static::creating(static function (self $favorite): void {
            $favorite->created_at ??= now();
        });
    }

    public function favoriteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
