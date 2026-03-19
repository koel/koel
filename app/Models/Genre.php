<?php

namespace App\Models;

use App\Builders\GenreBuilder;
use App\Observers\GenreObserver;
use Database\Factories\GenreFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 *
 * @method static GenreFactory factory(...$parameters)
 */
#[ObservedBy(GenreObserver::class)]
#[UseEloquentBuilder(GenreBuilder::class)]
class Genre extends Model
{
    use HasFactory;
    use Searchable;

    public const string NO_GENRE_PUBLIC_ID = 'no-genre';
    public const string NO_GENRE_NAME = '';

    public $timestamps = false;

    protected $fillable = [
        'public_id',
        'name',
    ];

    // @mago-ignore lint:no-redundant-method-override
    public static function query(): GenreBuilder
    {
        /** @var GenreBuilder */
        return parent::query();
    }

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public static function get(string $name): static
    {
        $name = trim($name);

        /** @var static */
        return static::query()->firstOrCreate(['name' => $name]);
    }

    /** @inheritdoc  */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
