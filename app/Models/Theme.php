<?php

namespace App\Models;

use App\Casts\ThemePropertiesCast;
use App\Values\Theme\ThemeProperties;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $name
 * @property string|null $thumbnail
 * @property int $user_id
 * @property ThemeProperties $properties
 * @property User $user
 */
class Theme extends Model
{
    use HasFactory;
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'properties' => ThemePropertiesCast::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
