<?php

namespace App\Models;

use App\Builders\RadioStationBuilder;
use App\Models\Concerns\MorphsToFavorites;
use App\Models\Contracts\Favoriteable;
use App\Models\Contracts\Permissionable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Laravel\Scout\Searchable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property int $user_id
 * @property User $user
 * @property string $url
 * @property ?string $logo The URL of the station's logo
 * @property ?string $logo_path The path to the station's logo
 * @property ?string $description
 * @property boolean $is_public
 * @property Carbon|string $created_at
 * @property string $name
 * @property-read ?boolean $favorite Whether the (scoped) user has favorited this radio station
 */
class RadioStation extends Model implements AuditableContract, Favoriteable, Permissionable
{
    use Auditable;
    use HasFactory;
    use HasUlids;
    use MorphsToFavorites;
    use Searchable;

    protected $guarded = [];
    public $incrementing = false;

    protected $with = ['user'];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'favorite' => 'boolean',
        'is_public' => 'boolean',
        'description' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function query(): RadioStationBuilder
    {
        /** @var RadioStationBuilder */
        return parent::query()->addSelect('radio_stations.*');
    }

    public function newEloquentBuilder($query): RadioStationBuilder
    {
        return new RadioStationBuilder($query);
    }

    protected function logo(): Attribute
    {
        return Attribute::get(static fn (?string $value): ?string => image_storage_url($value))->shouldCache();
    }

    protected function logoPath(): Attribute
    {
        return Attribute::get(function () {
            $logo = Arr::get($this->attributes, 'logo');

            return $logo ? image_storage_path($logo) : null;
        })->shouldCache();
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

    public static function getPermissionableIdentifier(): string
    {
        return 'id';
    }
}
