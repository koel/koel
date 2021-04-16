<?php

namespace App\Models;

use App\Traits\CanFilterByUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/**
 * @property int $user_id
 * @property Collection $songs
 * @property string $id
 * @property array $rules
 * @property bool $is_smart
 * @property string $name
 * @property user $user
 *
 * @method static Builder orderBy(string $field, string $order = 'asc')
 */
class Playlist extends Model
{
    use Searchable;
    use CanFilterByUser;
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $hidden = ['user_id', 'created_at', 'updated_at'];
    protected $guarded = [];
    protected $casts = [
        'user_id' => 'int',
        'rules' => 'array',
    ];
    protected $appends = ['is_smart'];

    protected static function booted(): void
    {
        static::creating(static function (self $playlist): void {
            $playlist->id = $playlist->id ?: Str::uuid()->toString();
        });
    }

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getIsSmartAttribute(): bool
    {
        return (bool) $this->rules;
    }

    /** @return array<mixed> */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
