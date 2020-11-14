<?php

namespace App\Models;

use App\Facades\Util;
use function App\Helpers\artist_image_path;
use function App\Helpers\artist_image_url;
use App\Traits\SupportsDeleteWhereIDsNotIn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int         $id
 * @property string      $name
 * @property string|null $image      Public URL to the artist's image
 * @property bool        $is_unknown If the artist is Unknown Artist
 * @property bool        $is_various If the artist is Various Artist
 * @property Collection  $songs
 * @property bool        $has_image  If the artist has a (non-default) image
 * @property string|null $image_path Absolute path to the artist's image
 *
 * @method static self find(int $id)
 * @method static self firstOrCreate(array $where, array $params = [])
 * @method static Builder where(...$params)
 * @method static self first()
 * @method static Builder whereName(string $name)
 */
class Artist extends Model
{
    use HasFactory;
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
    public static function getOrCreate(?string $name = null): self
    {
        // Remove the BOM from UTF-8/16/32, as it will mess up the database constraints.
        if ($encoding = Util::detectUTFEncoding($name)) {
            $name = mb_convert_encoding($name, 'UTF-8', $encoding);
        }

        return static::firstOrCreate(['name' => trim($name) ?: self::UNKNOWN_NAME]);
    }

    /**
     * Turn the image name into its absolute URL.
     */
    public function getImageAttribute(?string $value): ?string
    {
        return $value ? artist_image_url($value) : null;
    }

    public function getImagePathAttribute(): ?string
    {
        if (!$this->has_image) {
            return null;
        }

        return artist_image_path(array_get($this->attributes, 'image'));
    }

    public function getHasImageAttribute(): bool
    {
        $image = array_get($this->attributes, 'image');

        if (!$image) {
            return false;
        }

        return file_exists(artist_image_path($image));
    }
}
