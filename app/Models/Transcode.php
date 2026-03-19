<?php

namespace App\Models;

use App\Enums\SongStorageType;
use Database\Factories\TranscodeFactory;
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
 * @property ?int $file_size
 * @property string $hash
 * @property string $location
 *
 * @method static TranscodeFactory factory(...$parameters)
 */
class Transcode extends Model
{
    use HasFactory;
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'bit_rate' => 'int',
            'file_size' => 'int',
        ];
    }

    protected $with = ['song'];

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    public function isValid(): bool
    {
        // For cloud storage songs, since the transcoded file is stored in the cloud too,
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
