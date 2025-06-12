<?php

namespace App\Models;

use App\Enums\SongStorageType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;

/**
 * @property string $id
 * @property string $song_id
 * @property Song $song
 * @property-read SongStorageType $storage The storage type of the associated song.
 * @property int $bit_rate
 * @property string $hash
 * @property string $location
 */
class Transcode extends Model
{
    use HasFactory;
    use HasUuids;

    protected $guarded = [];
    protected $with = ['song'];

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    public function isValid(): bool
    {
        // For cloud storage songs, since the transcoded file is stored on the cloud too,
        // we assume the transcoded file is valid.
        if ($this->song->isStoredOnCloud()) {
            return true;
        }

        return File::isReadable($this->location) && File::hash($this->location) === $this->hash;
    }

    protected function storage(): Attribute
    {
        return Attribute::get(fn () => $this->song->storage)->shouldCache();
    }
}
