<?php

namespace App\Models;

use App\Traits\SupportsDeleteWhereIDsNotIn;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string      $cover           The album cover's file name
 * @property string|null $cover_path      The absolute path to the cover file
 * @property bool        $has_cover       If the album has a cover image
 * @property int         $id
 * @property string      $name            Name of the album
 * @property bool        $is_compilation  If the album is a compilation from multiple artists
 * @property Artist      $artist          The album's artist
 * @property int         $artist_id
 * @property Collection  $songs
 * @property bool        $is_unknown
 *
 * @method static self firstOrCreate(array $where, array $params = [])
 */
class Album extends Model
{
    use SupportsDeleteWhereIDsNotIn;

    const UNKNOWN_ID = 1;
    const UNKNOWN_NAME = 'Unknown Album';
    const UNKNOWN_COVER = 'unknown-album.png';

    protected $guarded = ['id'];
    protected $hidden = ['updated_at'];
    protected $casts = ['artist_id' => 'integer'];
    protected $appends = ['is_compilation'];

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

    /**
     * Get an album using some provided information.
     * If such is not found, a new album will be created using the information.
     */
    public static function get(Artist $artist, string $name, bool $isCompilation = false): self
    {
        // If this is a compilation album, its artist must be "Various Artists"
        if ($isCompilation) {
            $artist = Artist::getVariousArtist();
        }

        return static::firstOrCreate([
            'artist_id' => $artist->id,
            'name' => $name ?: self::UNKNOWN_NAME,
        ]);
    }

    public function setCoverAttribute(?string $value): void
    {
        $this->attributes['cover'] = $value ?: self::UNKNOWN_COVER;
    }

    public function getCoverAttribute(?string $value): string
    {
        return app()->staticUrl('public/img/covers/'.($value ?: self::UNKNOWN_COVER));
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

        return file_exists(public_path("/public/img/covers/$cover"));
    }

    public function getCoverPathAttribute(): ?string
    {
        $cover = array_get($this->attributes, 'cover');

        if (!$cover) {
            return null;
        }

        return public_path("/public/img/covers/$cover");
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
}
