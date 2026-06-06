<?php

namespace App\Models;

use App\Builders\RadioStationBuilder;
use App\Models\Concerns\MorphsToFavorites;
use App\Models\Contracts\Favoriteable;
use App\Observers\RadioStationObserver;
use Carbon\Carbon;
use Database\Factories\RadioStationFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property int $user_id
 * @property User $user
 * @property string $url
 * @property ?string $homepage_url The station's homepage URL
 * @property ?string $logo The station's logo file name
 * @property ?string $description
 * @property boolean $is_public
 * @property Carbon|string $created_at
 * @property string $name
 * @property-read ?boolean $favorite Whether the (scoped) user has favorited this radio station
 *
 * @method static RadioStationFactory factory(...$parameters)
 */
#[ObservedBy(RadioStationObserver::class)]
#[UseEloquentBuilder(RadioStationBuilder::class)]
#[Unguarded]
#[WithoutIncrementing]
class RadioStation extends Model implements AuditableContract, Favoriteable
{
    use Auditable;
    use HasFactory;
    use HasUlids;
    use MorphsToFavorites;
    use Searchable;

    protected $with = ['user'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'favorite' => 'boolean',
            'is_public' => 'boolean',
            'description' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function query(): RadioStationBuilder
    {
        /** @var RadioStationBuilder */
        return parent::query()->addSelect('radio_stations.*');
    }

    /** @inheritdoc */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'user_id' => $this->user_id,
        ];
    }
}
