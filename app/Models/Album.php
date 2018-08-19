<?php

namespace App\Models;

use App\Traits\SupportsDeleteWhereIDsNotIn;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string cover           The path to the album's cover
 * @property bool   has_cover       If the album has a cover image
 * @property int    id
 * @property string name            Name of the album
 * @property bool   is_compilation  If the album is a compilation from multiple artists
 * @property Artist artist          The album's artist
 * @property int    artist_id
 * @property Collection  songs
 * @property bool   is_unknown
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

    /**
     * An album belongs to an artist.
     *
     * @return BelongsTo
     */
    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    /**
     * An album can contain many songs.
     *
     * @return HasMany
     */
    public function songs()
    {
        return $this->hasMany(Song::class);
    }

    /**
     * Indicate if the album is unknown.
     *
     * @return bool
     */
    public function getIsUnknownAttribute()
    {
        return $this->id === self::UNKNOWN_ID;
    }

    /**
     * Get an album using some provided information.
     *
     * @param Artist $artist
     * @param string $name
     * @param bool   $isCompilation
     *
     * @return self
     */
    public static function get(Artist $artist, $name, $isCompilation = false)
    {
        // If this is a compilation album, its artist must be "Various Artists"
        if ($isCompilation) {
            $artist = Artist::getVariousArtist();
        }

        return self::firstOrCreate([
            'artist_id' => $artist->id,
            'name' => $name ?: self::UNKNOWN_NAME,
        ]);
    }

    /**
     * Set the album cover.
     *
     * @param string $value
     */
    public function setCoverAttribute($value)
    {
        $this->attributes['cover'] = $value ?: self::UNKNOWN_COVER;
    }

    /**
     * Get the album cover.
     *
     * @param string $value
     *
     * @return string
     */
    public function getCoverAttribute($value)
    {
        return app()->staticUrl('public/img/covers/'.($value ?: self::UNKNOWN_COVER));
    }

    /**
     * Determine if the current album has a cover.
     *
     * @return bool
     */
    public function getHasCoverAttribute()
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

    /**
     * Sometimes the tags extracted from getID3 are HTML entity encoded.
     * This makes sure they are always sane.
     *
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {
        return html_entity_decode($value);
    }

    /**
     * Determine if the album is a compilation.
     *
     * @return bool
     */
    public function getIsCompilationAttribute()
    {
        return $this->artist_id === Artist::VARIOUS_ID;
    }
}
