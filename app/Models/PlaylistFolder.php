<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\PlaylistFolderFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $name
 * @property ?string $parent_id
 * @property ?PlaylistFolder $parent
 * @property Collection<array-key, PlaylistFolder> $subfolders
 * @property User $user
 * @property Collection<array-key, Playlist> $playlists
 * @property int $user_id
 * @property Carbon $created_at
 * @property ?string $id
 *
 * @method static PlaylistFolderFactory factory(...$parameters)
 */
#[Guarded(['id'])]
class PlaylistFolder extends Model implements AuditableContract
{
    use Auditable;
    use HasFactory;
    use HasUuids;

    protected $with = ['user'];

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class, null, 'folder_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subfolders(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ownedBy(User $user): bool
    {
        return $this->user->is($user);
    }
}
