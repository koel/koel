<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int id The model ID
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

    public function getNameAttribute($value) 
    {
        return $value ?: self::UNKNOWN_NAME;
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
        $name = trim($name) ?: self::UNKNOWN_NAME;

        return self::firstOrCreate(compact('name'), compact('name'));
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
