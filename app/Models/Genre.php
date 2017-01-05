<?php

namespace App\Models;

use App\Facades\Util;
use App\Traits\SupportsDeleteWhereIDsNotIn;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int    id      The genre ID
 * @property string name    The genre name
 */
class Genre extends Model
{
    use SupportsDeleteWhereIDsNotIn;

    const UNKNOWN_ID = 1;
    const UNKNOWN_NAME = 'Unknown Genre';

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function songs()
    {
        return $this->hasMany(Song::class);
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
     * Get an genre object from their name.
     * If such is not found, a new genre will be created.
     *
     * @param string $name
     *
     * @return Genre
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
}
