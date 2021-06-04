<?php

namespace App\Models;

use App\Traits\SupportsDeleteWhereIDsNotIn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property string $cover The album cover's file name
 * @property string|null $cover_path The absolute path to the cover file
 * @property bool $has_cover If the album has a non-default cover image
 * @property int $id
 * @property string $name Name of the album
 * @property bool $is_compilation If the album is a compilation from multiple artists
 * @property Artist $artist The album's artist
 * @property int $artist_id
 * @property Collection $songs
 * @property bool $is_unknown If the album is the Unknown Album
 * @property string|null $thumbnail_name The file name of the album's thumbnail
 * @property string|null $thumbnail_path The full path to the thumbnail.
 *                                       Notice that this doesn't guarantee the thumbnail exists.
 * @property string|null $thumbnail The public URL to the album's thumbnail
 *
 * @method static self firstOrCreate(array $where, array $params = [])
 * @method static self|null find(int $id)
 * @method static Builder where(...$params)
 * @method static self first()
 * @method static Builder whereArtistIdAndName(int $id, string $name)
 * @method static orderBy(...$params)
 */
class Album extends Model
{
    use HasFactory;
    use Searchable;
    use SupportsDeleteWhereIDsNotIn;

    public const UNKNOWN_ID = 1;
    public const UNKNOWN_NAME = 'Unknown Album';
    public const UNKNOWN_COVER = 'unknown-album.png';

    protected $guarded = ['id'];
    protected $hidden = ['updated_at'];
    protected $casts = ['artist_id' => 'integer'];
    protected $appends = ['is_compilation'];

    /**
     * Get an album using some provided information.
     * If such is not found, a new album will be created using the information.
     */
    public static function getOrCreate(Artist $artist, ?string $name = null, bool $isCompilation = false): self
    {
        // If this is a compilation album, its artist must be "Various Artists"
        if ($isCompilation) {
            $artist = Artist::getVariousArtist();
        }

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

    public function getIsUnknownAttribute(): bool
    {
        return $this->id === self::UNKNOWN_ID;
    }

    public function setCoverAttribute(?string $value): void
    {
        $this->attributes['cover'] = $value ?: self::UNKNOWN_COVER;
    }

    public function getCoverAttribute(?string $value): string
    {
        return album_cover_url($value ?: self::UNKNOWN_COVER);
    }

    public function getHasCoverAttribute(): bool
    {
        $cover = array_get($this->attributes, 'cover');

        if (!$cover) {
            return false;
        }

        if ($cover === self::UNKNOWN_COVER) {
            return false;
        }

        return file_exists(album_cover_path($cover));
    }

    public function getCoverPathAttribute(): ?string
    {
        $cover = array_get($this->attributes, 'cover');

        return $cover ? album_cover_path($cover) : null;
    }

    /**
     * Sometimes the tags extracted from getID3 are HTML entity encoded.
     * This makes sure they are always sane.
     */
    public function getNameAttribute(string $value): string
    {
        return html_entity_decode($value);
    }

    public function getIsCompilationAttribute(): bool
    {
        return $this->artist_id === Artist::VARIOUS_ID;
    }

    public function getThumbnailNameAttribute(): ?string
    {
        if (!$this->has_cover) {
            return null;
        }

        $parts = pathinfo($this->cover_path);

        return sprintf('%s_thumb.%s', $parts['filename'], $parts['extension']);
    }

    public function getThumbnailPathAttribute(): ?string
    {
        return $this->thumbnail_name ? album_cover_path($this->thumbnail_name) : null;
    }

    public function getThumbnailAttribute(): ?string
    {
        return $this->thumbnail_name ? album_cover_url($this->thumbnail_name) : null;
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
