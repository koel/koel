<?php

namespace App\Models;

use App\Builders\AlbumBuilder;
use App\Models\Concerns\MorphsToEmbeds;
use App\Models\Concerns\MorphsToFavorites;
use App\Models\Concerns\SupportsDeleteWhereValueNotIn;
use App\Models\Contracts\Embeddable;
use App\Models\Contracts\Favoriteable;
use App\Models\Contracts\Permissionable;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Laravel\Scout\Searchable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property ?int $year
 * @property ?string $cover_path The absolute path to the cover file
 * @property ?string $thumbnail The public URL to the album's thumbnail
 * @property ?string $thumbnail_name The file name of the album's thumbnail
 * @property ?string $thumbnail_path The full path to the thumbnail. This doesn't guarantee the thumbnail exists.
 * @property Artist $artist The album's artist
 * @property Carbon $created_at
 * @property Collection<array-key, Song> $songs
 * @property User $user
 * @property bool $has_cover If the album has a non-default cover image
 * @property bool $is_unknown If the album is the Unknown Album
 * @property string $artist_id
 * @property string $artist_name
 * @property string $id
 * @property int $user_id
 * @property string $cover The album cover's URL
 * @property string $name Name of the album
 * @property ?boolean $favorite Whether the album is liked by the scoped user
 */
class Album extends Model implements AuditableContract, Embeddable, Favoriteable, Permissionable
{
    use Auditable;
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasUlids;
    use MorphsToEmbeds;
    use MorphsToFavorites;
    use Searchable;
    use SupportsDeleteWhereValueNotIn;

    public const UNKNOWN_NAME = 'Unknown Album';

    protected $guarded = ['id'];
    protected $hidden = ['updated_at'];

    protected $with = ['artist'];

    /** @deprecated */
    protected $appends = ['is_compilation'];

    public static function query(): AlbumBuilder
    {
        /** @var AlbumBuilder */
        return parent::query()->addSelect('albums.*');
    }

    public function newEloquentBuilder($query): AlbumBuilder
    {
        return new AlbumBuilder($query);
    }

    /**
     * Get an album using some provided information.
     * If such is not found, a new album will be created using the information.
     */
    public static function getOrCreate(Artist $artist, ?string $name = null): static
    {
        return static::query()->firstOrCreate([ // @phpstan-ignore-line
            'artist_id' => $artist->id,
            'artist_name' => $artist->name,
            'user_id' => $artist->user_id,
            'name' => trim($name) ?: self::UNKNOWN_NAME,
        ]);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function belongsToUser(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    protected function isUnknown(): Attribute
    {
        return Attribute::get(fn (): bool => $this->name === self::UNKNOWN_NAME);
    }

    protected function cover(): Attribute
    {
        return Attribute::get(static fn (?string $value): ?string => image_storage_url($value));
    }

    protected function hasCover(): Attribute
    {
        return Attribute::get(
            fn (): bool => $this->cover_path && (app()->runningUnitTests() || File::exists($this->cover_path))
        );
    }

    protected function coverPath(): Attribute
    {
        return Attribute::get(function () {
            $cover = Arr::get($this->attributes, 'cover');

            return $cover ? image_storage_path($cover) : null;
        });
    }

    /**
     * Sometimes the tags extracted from getID3 are HTML entity encoded.
     * This makes sure they are always sane.
     */
    protected function name(): Attribute
    {
        return Attribute::get(static fn (?string $value) => html_entity_decode($value))->shouldCache();
    }

    protected function thumbnailName(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (!$this->has_cover) {
                return null;
            }

            $parts = pathinfo($this->cover_path);

            return sprintf('%s_thumb.%s', $parts['filename'], $parts['extension']);
        })->shouldCache();
    }

    protected function thumbnailPath(): Attribute
    {
        return Attribute::get(fn () => $this->thumbnail_name ? image_storage_path($this->thumbnail_name) : null);
    }

    protected function thumbnail(): Attribute
    {
        return Attribute::get(fn () => $this->thumbnail_name ? image_storage_url($this->thumbnail_name) : null);
    }

    /** @deprecated Only here for backward compat with mobile apps */
    protected function isCompilation(): Attribute
    {
        return Attribute::get(fn () => $this->artist->is_various);
    }

    /** @inheritdoc */
    public function toSearchableArray(): array
    {
        $array = [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
        ];

        if (
            $this->artist_name
            && $this->artist_name !== Artist::UNKNOWN_NAME
            && $this->artist_name !== Artist::VARIOUS_NAME
        ) {
            $array['artist'] = $this->artist_name;
        }

        return $array;
    }

    public static function getPermissionableIdentifier(): string
    {
        return 'id';
    }
}
