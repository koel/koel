<?php

namespace App\Models;

use App\Facades\Lastfm;
use App\Facades\Util;
use App\Traits\SupportsDeleteWhereIDsNotIn;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Log;

/**
 * @property int    id      The model ID
 * @property string name    The artist name
 * @property string image
 * @property bool   is_unknown
 * @property bool   is_various
 * @property \Illuminate\Database\Eloquent\Collection songs
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

    public function albums()
    {
        return $this->hasMany(Album::class);
    }

    public function songs()
    {
        return $this->hasManyThrough(Song::class, Album::class);
    }

    public function getIsUnknownAttribute()
    {
        return $this->id === self::UNKNOWN_ID;
    }

    public function getVariousAttribute()
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
        if (!$this->image && is_string($image) && ini_get('allow_url_fopen')) {
            try {
                $extension = explode('.', $image);
                $fileName = uniqid().'.'.trim(strtolower(last($extension)), '. ');
                $coverPath = app()->publicPath().'/public/img/artists/'.$fileName;

                file_put_contents($coverPath, file_get_contents($image));

                $this->update(['image' => $fileName]);
                $info['image'] = $this->image;
            } catch (Exception $e) {
                Log::error($e);
            }
        }

        return $info;
    }

    /**
     * Get songs *contributed* (in compilation albums) by the artist.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getContributedSongs()
    {
        return Song::whereContributingArtistId($this->id)->get();
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
        return  $value ? app()->staticUrl("public/img/artists/$value") : null;
    }
}
