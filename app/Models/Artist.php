<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Facades\Util;

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
            $name = iconv($encoding, 'UTF-8//IGNORE', $name);
        }

        $name = trim($name) ?: self::UNKNOWN_NAME;

        return self::firstOrCreate(compact('name'), compact('name'));
    }
}
