<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string cover       The path to the album's cover
 * @property bool   has_cover   If the album has a cover image
 * @property int    id
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
        $fileName = uniqid().'.'.strtolower($extension[1]);
        $coverPath = app()->publicPath().'/public/img/covers/'.$fileName;

        file_put_contents($coverPath, $cover['data']);

        $this->update(['cover' => $fileName]);
    }

    public function setCoverAttribute($value)
    {
        $this->attributes['cover'] = $value ?: self::UNKNOWN_COVER;
    }

    public function getCoverAttribute($value)
    {
        return '/public/img/covers/'.($value ?: self::UNKNOWN_COVER);
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
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = html_entity_decode($value);
    }
}
