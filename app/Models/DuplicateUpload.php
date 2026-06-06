<?php

namespace App\Models;

use App\Enums\SongStorageType;
use Carbon\Carbon;
use Database\Factories\DuplicateUploadFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $user_id
 * @property string|null $existing_song_id
 * @property string $location
 * @property SongStorageType $storage
 * @property Carbon $created_at
 * @property Song|null $existingSong
 * @property User $user
 *
 * @method static DuplicateUploadFactory factory(...$parameters)
 */
#[Guarded(['id'])]
#[Hidden(['updated_at'])]
class DuplicateUpload extends Model
{
    use HasFactory;
    use HasUuids;

    protected $with = ['user'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'storage' => SongStorageType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function existingSong(): BelongsTo
    {
        return $this->belongsTo(Song::class, 'existing_song_id');
    }
}
