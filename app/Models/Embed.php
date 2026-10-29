<?php

namespace App\Models;

use App\Enums\EmbeddableType;
use Carbon\Carbon;
use Database\Factories\EmbedFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Carbon $created_at
 * @property User $user
 * @property string $id
 * @property ?string $embeddable_id
 * @property EmbeddableType $embeddable_type
 *
 * @method static EmbedFactory factory(...$parameters)
 */
#[Unguarded]
class Embed extends Model
{
    use HasFactory;
    use HasUlids;

    protected $with = ['user'];

    protected function casts(): array
    {
        return [
            'embeddable_type' => EmbeddableType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
