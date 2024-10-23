<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $name
 * @property User $user
 * @property Collection<array-key, Playlist> $playlists
 * @property int $user_id
 * @property Carbon $created_at
 */
class PlaylistFolder extends Model
{
    use HasFactory;
    use HasUuids;

    protected $guarded = ['id'];
    protected $with = ['user'];

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class, null, 'folder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ownedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
