<?php

namespace App\Models;

use App\Builders\ArtistBuilder;
use App\Facades\License;
use App\Facades\Util;
use App\Models\Concerns\MorphsToEmbeds;
use App\Models\Concerns\MorphsToFavorites;
use App\Models\Concerns\SupportsDeleteWhereValueNotIn;
use App\Models\Contracts\Embeddable;
use App\Models\Contracts\Favoriteable;
use App\Models\Contracts\Permissionable;
use Carbon\Carbon;
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
 * @property ?string $image Public URL to the artist's image
 * @property ?string $image_path Absolute path to the artist's image
 * @property Carbon $created_at
 * @property Collection<array-key, Album> $albums
 * @property Collection<array-key, Song> $songs
 * @property User $user
 * @property bool $has_image If the artist has a (non-default) image
 * @property bool $is_unknown If the artist is Unknown Artist
 * @property bool $is_various If the artist is Various Artist
 * @property string $id
 * @property int $user_id The ID of the user that owns this artist
 * @property string $name
 */
class Artist extends Model implements AuditableContract, Embeddable, Favoriteable, Permissionable
{
    use Auditable;
    use HasFactory;
    use HasUlids;
    use MorphsToEmbeds;
    use MorphsToFavorites;
    use Searchable;
    use SupportsDeleteWhereValueNotIn;

    public const UNKNOWN_NAME = 'Unknown Artist';
    public const VARIOUS_NAME = 'Various Artists';

    protected $guarded = ['id'];
    protected $hidden = ['created_at', 'updated_at'];

    public static function query(): ArtistBuilder
    {
        /** @var ArtistBuilder */
        return parent::query()->addSelect('artists.*');
    }

    public function newEloquentBuilder($query): ArtistBuilder
    {
        return new ArtistBuilder($query);
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
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

    protected function isVarious(): Attribute
    {
        return Attribute::get(fn (): bool => $this->name === self::VARIOUS_NAME);
    }

    /**
     * Get an Artist object from their name (and if Koel Plus, belonging to a specific user).
     * If such is not found, a new artist will be created.
     */
    public static function getOrCreate(User $user, ?string $name = null): self
    {
        // Remove the BOM from UTF-8/16/32, as it will mess up the database constraints.
        $encoding = Util::detectUTFEncoding($name);

        if ($encoding) {
            $name = mb_convert_encoding($name, 'UTF-8', $encoding);
        }

        $name = trim($name) ?: self::UNKNOWN_NAME;

        // In the Community license, all artists are shared, so we determine the first artist by the name only.
        // In the Plus license, artists are user-specific, so we create or return the artist for the given user.
        $where = ['name' => $name];

        if (License::isPlus()) {
            $where['user_id'] = $user->id;
        }

        return static::query()->where($where)->firstOr(static function () use ($user, $name): Artist {
            return static::query()->create([
                'user_id' => $user->id,
                'name' => $name,
            ]);
        });
    }

    /**
     * Sometimes the tags extracted from getID3 are HTML entity encoded.
     * This makes sure they are always sane.
     */
    protected function name(): Attribute
    {
        return Attribute::get(static fn (string $value): string => html_entity_decode($value) ?: self::UNKNOWN_NAME);
    }

    /**
     * Turn the image name into its absolute URL.
     */
    protected function image(): Attribute
    {
        return Attribute::get(static fn (?string $value): ?string => image_storage_url($value));
    }

    protected function imagePath(): Attribute
    {
        return Attribute::get(fn (): ?string => image_storage_path(Arr::get($this->attributes, 'image')));
    }

    protected function hasImage(): Attribute
    {
        return Attribute::get(function (): bool {
            $image = Arr::get($this->attributes, 'image');

            return $image && (app()->runningUnitTests() || File::exists(image_storage_path($image)));
        });
    }

    /** @return array<mixed> */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
        ];
    }

    public static function getPermissionableIdentifier(): string
    {
        return 'id';
    }
}
