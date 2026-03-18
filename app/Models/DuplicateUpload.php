<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Song|null $existingSong
 * @property User $user
 * @property int $id
 * @property int $user_id
 * @property int|null $existing_song_id
 * @property string $file_path
 *
 * @method static \Database\Factories\DuplicateUploadFactory factory(...$parameters)
 */
class DuplicateUpload extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $hidden = ['updated_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function existingSong(): BelongsTo
    {
        return $this->belongsTo(Song::class, 'existing_song_id');
    }
}
