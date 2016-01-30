<?php

namespace App\Models;

use App\Facades\Lastfm;
use App\Facades\Util;
use Illuminate\Database\Eloquent\Model;
use Log;

/**
 * @property int    id      The model ID
 * @property string name    The artist name
 */
class Artist extends Model
{
    const UNKNOWN_ID = 1;
    const UNKNOWN_NAME = 'Unknown Artist';

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function albums()
    {
        return $this->hasMany(Album::class);
    }

    public function isUnknown()
    {
        return $this->id === self::UNKNOWN_ID;
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
        if ($this->id === self::UNKNOWN_ID) {
            return false;
        }

        $info = Lastfm::getArtistInfo($this->name);

        // If our current artist has no image, and Last.fm has one, copy the image for our local use.
        if (!$this->image &&
            is_string($image = array_get($info, 'image')) &&
            ini_get('allow_url_fopen')
        ) {
            try {
                $extension = explode('.', $image);
                $fileName = uniqid().'.'.trim(strtolower(last($extension)), '. ');
                $coverPath = app()->publicPath().'/public/img/artists/'.$fileName;

                file_put_contents($coverPath, file_get_contents($image));

                $this->update(['image' => $fileName]);
                $info['image'] = $this->image;
            } catch (\Exception $e) {
                Log::error($e);
            }
        }

        return $info;
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
