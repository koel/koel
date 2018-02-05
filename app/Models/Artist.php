<?php

namespace App\Models;

use App\Facades\Lastfm;
use App\Facades\Util;
use App\Traits\SupportsDeleteWhereIDsNotIn;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Log;

/**
 * @property int    id      The model ID
 * @property string name    The artist name
 * @property string image
 * @property bool   is_unknown
 * @property bool   is_various
 * @property Collection songs
 * @property bool   has_image
 */
class Artist extends Model
{
    use SupportsDeleteWhereIDsNotIn;

    const UNKNOWN_ID = 1;
    const UNKNOWN_NAME = 'Unknown Artist';
    const VARIOUS_ID = 2;
    const VARIOUS_NAME = 'Various Artists';

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * An artist can have many albums.
     *
     * @return HasMany
     */
    public function albums()
    {
        return $this->hasMany(Album::class);
    }

    /**
     * An artist can have many songs.
     * Unless he is Rick Astley.
     *
     * @return HasManyThrough
     */
    public function songs()
    {
        return $this->hasManyThrough(Song::class, Album::class);
    }

    /**
     * Indicate if the artist is unknown.
     *
     * @return bool
     */
    public function getIsUnknownAttribute()
    {
        return $this->id === self::UNKNOWN_ID;
    }

    /**
     * Indicate if the artist is the special "Various Artists".
     *
     * @return bool
     */
    public function getIsVariousAttribute()
    {
        return $this->id === self::VARIOUS_ID;
    }

    /**
     * Get the "Various Artists" object.
     *
     * @return Artist
     */
    public static function getVariousArtist()
    {
        return self::find(self::VARIOUS_ID);
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
        return html_entity_decode($value ?: self::UNKNOWN_NAME);
    }

    /**
     * Get an Artist object from their name.
     * If such is not found, a new artist will be created.
     *
     * @param string $name
     *
     * @return Artist
     */
    public static function get($name)
    {
        // Remove the BOM from UTF-8/16/32, as it will mess up the database constraints.
        if ($encoding = Util::detectUTFEncoding($name)) {
            $name = mb_convert_encoding($name, 'UTF-8', $encoding);
        }

        $name = trim($name) ?: self::UNKNOWN_NAME;

        return self::firstOrCreate(compact('name'), compact('name'));
    }

    /**
     * Get extra information about the artist from Last.fm.
     *
     * @return array|false
     */
    public function getInfo()
    {
        if ($this->is_unknown) {
            return false;
        }

        $info = Lastfm::getArtistInfo($this->name);
        $image = array_get($info, 'image');

        // If our current artist has no image, and Last.fm has one, copy the image for our local use.
        if (!$this->has_image && is_string($image) && ini_get('allow_url_fopen')) {
            try {
                $extension = explode('.', $image);
                $this->writeImageFile(file_get_contents($image), last($extension));
                $info['image'] = $this->image;
            } catch (Exception $e) {
                Log::error($e);
            }
        }

        return $info;
    }

    /**
     * Write an artist image file with binary data and update the Artist with the new cover file.
     *
     * @param string $binaryData
     * @param string $extension   The file extension
     * @param string $destination The destination path. Automatically generated if empty.
     */
    public function writeImageFile($binaryData, $extension, $destination = '')
    {
        $extension = trim(strtolower($extension), '. ');
        $destination = $destination ?: $this->generateRandomImagePath($extension);
        file_put_contents($destination, $binaryData);

        $this->update(['image' => basename($destination)]);
    }

    /**
     * Generate a random path for the artist's image.
     *
     * @param string $extension The extension of the cover (without dot)
     *
     * @return string
     */
    private function generateRandomImagePath($extension)
    {
        return app()->publicPath().'/public/img/artists/'.uniqid('', true).".$extension";
    }

    /**
     * Turn the image name into its absolute URL.
     *
     * @param mixed $value
     *
     * @return string|null
     */
    public function getImageAttribute($value)
    {
        return $value ? app()->staticUrl("public/img/artists/$value") : null;
    }

    public function getHasImageAttribute()
    {
        $image = array_get($this->attributes, 'image');

        if (!$image) {
            return false;
        }

        if (!file_exists(public_path("public/img/artists/$image"))) {
            return false;
        }

        return true;
    }
}
