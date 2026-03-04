<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Collection<User>|array<array-key, User> $users
 */

class Organization extends Model
{
    use HasUlids;
    use HasFactory;

    public const DEFAULT_SLUG = 'koel';

    protected $guarded = ['id'];

    public static function default(): Organization
    {
        return once(static fn () => self::query()->firstOrCreate(['slug' => self::DEFAULT_SLUG], ['name' => 'Koel']));
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
