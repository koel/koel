<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;

/**
 * @property string $cover The album cover's file name
 * @property string|null $cover_path The absolute path to the cover file
 * @property bool $has_cover If the album has a non-default cover image
 * @property int $id
 * @property string $name Name of the album
 * @property Artist $artist The album's artist
 * @property int $artist_id
 * @property Collection $songs
 * @property bool $is_unknown If the album is the Unknown Album
 * @property string|null $thumbnail_name The file name of the album's thumbnail
 * @property string|null $thumbnail_path The full path to the thumbnail.
 *                                       Notice that this doesn't guarantee the thumbnail exists.
 * @property string|null $thumbnail The public URL to the album's thumbnail
 * @property Carbon $created_at
 *
 * @method static self firstOrCreate(array $where, array $params = [])
 * @method static self|null find(int $id)
 * @method static Builder where(...$params)
 * @method static self first()
 * @method static Builder whereArtistIdAndName(int $id, string $name)
 * @method static orderBy(...$params)
 * @method static Builder latest()
 * @method static Builder whereName(string $name)
 */
class Album extends Model
{
    use HasFactory;
    use Searchable;
    use SupportsDeleteWhereIDsNotIn;

    public const UNKNOWN_ID = 1;
    public const UNKNOWN_NAME = 'Unknown Album';

    protected $guarded = ['id'];
    protected $hidden = ['updated_at'];
    protected $casts = ['artist_id' => 'integer'];

    /**
     * Get an album using some provided information.
     * If such is not found, a new album will be created using the information.
     */
    public static function getOrCreate(Artist $artist, ?string $name = null): self
    {
        return static::firstOrCreate([
            'artist_id' => $artist->id,
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

    protected function isUnknown(): Attribute
    {
        return Attribute::get(fn () => $this->id === self::UNKNOWN_ID);
    }

    protected function cover(): Attribute
    {
        return Attribute::get(static fn (?string $value) => $value ? album_cover_url($value) : '');
    }

    protected function hasCover(): Attribute
    {
        return Attribute::get(function () {
            $cover = array_get($this->attributes, 'cover');

            return $cover && file_exists(album_cover_path($cover));
        });
    }

    protected function coverPath(): Attribute
    {
        return Attribute::get(function () {
            $cover = array_get($this->attributes, 'cover');

            return $cover ? album_cover_path($cover) : null;
        });
    }

    /**
     * Sometimes the tags extracted from getID3 are HTML entity encoded.
     * This makes sure they are always sane.
     */
    protected function name(): Attribute
    {
        return Attribute::get(static fn (string $value) => html_entity_decode($value));
    }

    protected function thumbnailName(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (!$this->has_cover) {
                return null;
            }

            $parts = pathinfo($this->cover_path);

            return sprintf('%s_thumb.%s', $parts['filename'], $parts['extension']);
        });
    }

    protected function thumbnailPath(): Attribute
    {
        return Attribute::get(fn () => $this->thumbnail_name ? album_cover_path($this->thumbnail_name) : null);
    }

    protected function thumbnail(): Attribute
    {
        return Attribute::get(fn () => $this->thumbnail_name ? album_cover_url($this->thumbnail_name) : null);
    }

    public function scopeIsStandard(Builder $query): Builder
    {
        return $query->whereNot('albums.id', self::UNKNOWN_ID);
    }

    public static function withMeta(User $scopedUser): BuilderContract
    {
        return static::query()
            ->with('artist')
            ->leftJoin('songs', 'albums.id', '=', 'songs.album_id')
            ->leftJoin('interactions', static function (JoinClause $join) use ($scopedUser): void {
                $join->on('songs.id', '=', 'interactions.song_id')
                    ->where('interactions.user_id', $scopedUser->id);
            })
            ->groupBy('albums.id')
            ->select(
                'albums.*',
                DB::raw('CAST(SUM(interactions.play_count) AS INTEGER) AS play_count')
            )
            ->withCount('songs AS song_count')
            ->withSum('songs AS length', 'length');
    }

    /** @return array<mixed> */
    public function toSearchableArray(): array
    {
        $array = [
            'id' => $this->id,
            'name' => $this->name,
        ];

        if (!$this->artist->is_unknown && !$this->artist->is_various) {
            $array['artist'] = $this->artist->name;
        }

        return $array;
    }
}
