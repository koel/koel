<?php

namespace App\Models;

use App\Facades\Util;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property string|null $image Public URL to the artist's image
 * @property bool $is_unknown If the artist is Unknown Artist
 * @property bool $is_various If the artist is Various Artist
 * @property Collection $songs
 * @property bool $has_image If the artist has a (non-default) image
 * @property string|null $image_path Absolute path to the artist's image
 * @property float|string $length Total length of the artist's songs in seconds (dynamically calculated)
 * @property string|int $play_count Total number of times the artist has been played (dynamically calculated)
 * @property string|int $song_count Total number of songs by the artist (dynamically calculated)
 * @property string|int $album_count Total number of albums by the artist (dynamically calculated)
 * @property Carbon $created_at
 *
 * @method static self find(int $id)
 * @method static self firstOrCreate(array $where, array $params = [])
 * @method static Builder where(...$params)
 * @method static self first()
 * @method static Builder whereName(string $name)
 * @method static Builder orderBy(...$params)
 * @method static Builder join(...$params)
 */
class Artist extends Model
{
    use HasFactory;
    use Searchable;
    use SupportsDeleteWhereValueNotIn;

    public const UNKNOWN_ID = 1;
    public const UNKNOWN_NAME = 'Unknown Artist';
    public const VARIOUS_ID = 2;
    public const VARIOUS_NAME = 'Various Artists';

    protected $guarded = ['id'];
    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Get an Artist object from their name.
     * If such is not found, a new artist will be created.
     */
    public static function getOrCreate(?string $name = null): self
    {
        // Remove the BOM from UTF-8/16/32, as it will mess up the database constraints.
        $encoding = Util::detectUTFEncoding($name);

        if ($encoding) {
            $name = mb_convert_encoding($name, 'UTF-8', $encoding);
        }

        return static::firstOrCreate(['name' => trim($name) ?: self::UNKNOWN_NAME]);
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class);
    }

    protected function isUnknown(): Attribute
    {
        return Attribute::get(fn (): bool => $this->id === self::UNKNOWN_ID);
    }

    protected function isVarious(): Attribute
    {
        return Attribute::get(fn (): bool => $this->id === self::VARIOUS_ID);
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
        return Attribute::get(static fn (?string $value): ?string => artist_image_url($value));
    }

    protected function imagePath(): Attribute
    {
        return Attribute::get(fn (): ?string => artist_image_path(Arr::get($this->attributes, 'image')));
    }

    protected function hasImage(): Attribute
    {
        return Attribute::get(function (): bool {
            $image = Arr::get($this->attributes, 'image');

            return $image && file_exists(artist_image_path($image));
        });
    }

    public function scopeIsStandard(Builder $query): Builder
    {
        return $query->whereNotIn('artists.id', [self::UNKNOWN_ID, self::VARIOUS_ID]);
    }

    public static function withMeta(User $scopedUser): BuilderContract
    {
        return static::query()
            ->leftJoin('songs', 'artists.id', '=', 'songs.artist_id')
            ->leftJoin('interactions', static function (JoinClause $join) use ($scopedUser): void {
                $join->on('interactions.song_id', '=', 'songs.id')
                    ->where('interactions.user_id', $scopedUser->id);
            })
            ->groupBy('artists.id')
            ->select(['artists.*', DB::raw('CAST(SUM(interactions.play_count) AS UNSIGNED) AS play_count')])
            ->withCount('albums AS album_count', 'songs AS song_count')
            ->withSum('songs AS length', 'length');
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
