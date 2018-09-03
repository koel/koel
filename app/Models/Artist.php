<?php

namespace App\Models;

use App\Facades\Util;
use App\Traits\SupportsDeleteWhereIDsNotIn;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int    $id
 * @property string $name
 * @property string $image
 * @property bool   $is_unknown
 * @property bool   $is_various
 * @property Collection $songs
 * @property bool   $has_image
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

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    /**
     * An artist can have many songs.
     * Unless he is Rick Astley.
     */
    public function songs(): HasManyThrough
    {
        return $this->hasManyThrough(Song::class, Album::class);
    }

    public function getIsUnknownAttribute(): bool
    {
        return $this->id === self::UNKNOWN_ID;
    }

    public function getIsVariousAttribute(): bool
    {
        return $this->id === self::VARIOUS_ID;
    }

    public static function getVariousArtist(): self
    {
        return static::find(self::VARIOUS_ID);
    }

    /**
     * Sometimes the tags extracted from getID3 are HTML entity encoded.
     * This makes sure they are always sane.
     */
    public function getNameAttribute(string $value): string
    {
        return html_entity_decode($value ?: self::UNKNOWN_NAME);
    }

    /**
     * Get an Artist object from their name.
     * If such is not found, a new artist will be created.
     */
    public static function get(string $name): self
    {
        // Remove the BOM from UTF-8/16/32, as it will mess up the database constraints.
        if ($encoding = Util::detectUTFEncoding($name)) {
            $name = mb_convert_encoding($name, 'UTF-8', $encoding);
        }

        $name = trim($name) ?: self::UNKNOWN_NAME;

        return static::firstOrCreate(compact('name'), compact('name'));
    }

    /**
     * Turn the image name into its absolute URL.
     */
    public function getImageAttribute(?string $value): ?string
    {
        return $value ? app()->staticUrl("public/img/artists/$value") : null;
    }

    public function getHasImageAttribute(): bool
    {
        $image = array_get($this->attributes, 'image');

        if (!$image) {
            return false;
        }

        return file_exists(public_path("public/img/artists/$image"));
    }
}
