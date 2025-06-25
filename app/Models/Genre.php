<?php

namespace App\Models;

use App\Builders\GenreBuilder;
use App\Helpers\Ulid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 */
class Genre extends Model
{
    use HasFactory;
    use Searchable;

    public const NO_GENRE_PUBLIC_ID = 'no-genre';
    public const NO_GENRE_NAME = 'No Genre';

    public $timestamps = false;

    protected $fillable = [
        'public_id',
        'name',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(static function (self $genre): void {
            $genre->public_id ??= Ulid::generate();
        });
    }

    public static function query(): GenreBuilder
    {
        /** @var GenreBuilder */
        return parent::query();
    }

    public function newEloquentBuilder($query): GenreBuilder
    {
        return new GenreBuilder($query);
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
        return static::query()->firstOrCreate(
            ['name' => $name],
        );
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
