<?php

namespace App\Models;

use App\Facades\Lastfm;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string cover       The path to the album's cover
 * @property bool   has_cover   If the album has a cover image
 * @property int    id
 * @property string name        Name of the album
 * @property Artist artist      The album's artist
 */
class Album extends Model
{
    const UNKNOWN_ID = 1;
    const UNKNOWN_NAME = 'Unknown Album';
    const UNKNOWN_COVER = 'unknown-album.png';

    protected $guarded = ['id'];
    protected $hidden = ['created_at', 'updated_at'];

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function songs()
    {
        return $this->hasMany(Song::class);
    }

    public function isUnknown()
    {
        return $this->id === self::UNKNOWN_ID;
    }

    /**
     * Get an album using some provided information.
     *
     * @param Artist $artist
     * @param        $name
     *
     * @return self
     */
    public static function get(Artist $artist, $name)
    {
        // If an empty name is provided, turn it into our "Unknown Album"
        $name = $name ?: self::UNKNOWN_NAME;

        $album = self::firstOrCreate([
            'artist_id' => $artist->id,
            'name' => $name,
        ]);

        return $album;
    }

    /**
     * Get extra information about the album from Last.fm.
     *
     * @return array|false
     */
    public function getInfo()
    {
        if ($this->isUnknown()) {
            return false;
        }

        $info = Lastfm::getAlbumInfo($this->name, $this->artist->name);

        // If our current album has no cover, and Last.fm has one, why don't we steal it?
        // Great artists steal for their great albums!
        if (!$this->has_cover &&
            is_string($image = array_get($info, 'image')) &&
            ini_get('allow_url_fopen')
        ) {
            $extension = explode('.', $image);
            $this->writeCoverFile(file_get_contents($image), last($extension));
            $info['cover'] = $this->cover;
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
     * @param string $extension  The file extension
     */
    private function writeCoverFile($binaryData, $extension)
    {
        $extension = trim(strtolower($extension), '. ');
        $fileName = uniqid().".$extension";
        $coverPath = app()->publicPath().'/public/img/covers/'.$fileName;

        file_put_contents($coverPath, $binaryData);

        $this->update(['cover' => $fileName]);
    }

    public function setCoverAttribute($value)
    {
        $this->attributes['cover'] = $value ?: self::UNKNOWN_COVER;
    }

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
        return $this->cover !== $this->getCoverAttribute(null);
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
}
