<?php

namespace App\Models;

use App\Facades\Lastfm;
use App\Traits\SupportsDeleteWhereIDsNotIn;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Log;

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
     * Get extra information about the album from Last.fm.
     *
     * @return array|false
     */
    public function getInfo()
    {
        if ($this->is_unknown) {
            return false;
        }

        $info = Lastfm::getAlbumInfo($this->name, $this->artist->name);
        $image = array_get($info, 'image');

        // If our current album has no cover, and Last.fm has one, why don't we steal it?
        // Great artists steal for their great albums!
        if (!$this->has_cover && is_string($image) && ini_get('allow_url_fopen')) {
            try {
                $extension = explode('.', $image);
                $this->writeCoverFile(file_get_contents($image), last($extension));
                $info['cover'] = $this->cover;
            } catch (Exception $e) {
                Log::error($e);
            }
        }

        return $info;
    }

    /**
     * Generate a cover from provided data.
     *
     * @param array $cover The cover data in array format, extracted by getID3.
     *                     For example:
     *                     [
     *                     'data' => '<binary data>',
     *                     'image_mime' => 'image/png',
     *                     'image_width' => 512,
     *                     'image_height' => 512,
     *                     'imagetype' => 'PNG', // not always present
     *                     'picturetype' => 'Other',
     *                     'description' => '',
     *                     'datalength' => 7627,
     *                     ]
     */
    public function generateCover(array $cover)
    {
        $extension = explode('/', $cover['image_mime']);
        $extension = empty($extension[1]) ? 'png' : $extension[1];

        $this->writeCoverFile($cover['data'], $extension);
    }

    /**
     * Write a cover image file with binary data and update the Album with the new cover file.
     *
     * @param string $binaryData
     * @param string $extension   The file extension
     * @param string $destination The destination path. Automatically generated if empty.
     */
    public function writeCoverFile($binaryData, $extension, $destination = '')
    {
        $extension = trim(strtolower($extension), '. ');
        $destination = $destination ?: $this->generateRandomCoverPath($extension);
        file_put_contents($destination, $binaryData);

        $this->update(['cover' => basename($destination)]);
    }

    /**
     * Copy a cover file from an existing image on the system.
     *
     * @param string $source      The original image's full path.
     * @param string $destination The destination path. Automatically generated if empty.
     */
    public function copyCoverFile($source, $destination = '')
    {
        $extension = pathinfo($source, PATHINFO_EXTENSION);
        $destination = $destination ?: $this->generateRandomCoverPath($extension);
        copy($source, $destination);

        $this->update(['cover' => basename($destination)]);
    }

    /**
     * Generate a random path for the cover image.
     *
     * @param string $extension The extension of the cover (without dot)
     *
     * @return string
     */
    private function generateRandomCoverPath($extension)
    {
        return app()->publicPath().'/public/img/covers/'.uniqid('', true).".$extension";
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
