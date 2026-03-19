<?php

namespace App\Models;

use App\Casts\SmartPlaylistRulesCast;
use App\Models\Concerns\MorphsToEmbeds;
use App\Models\Concerns\Playlists\ManagesCollaborators;
use App\Models\Concerns\Playlists\ManagesPlayables;
use App\Models\Contracts\Embeddable;
use App\Models\Song as Playable;
use App\Observers\PlaylistObserver;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Carbon\Carbon;
use Database\Factories\PlaylistFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $name
 * @property string $description
 * @property bool $is_smart
 * @property User $owner
 * @property ?SmartPlaylistRuleGroupCollection $rule_groups
 * @property ?SmartPlaylistRuleGroupCollection $rules
 * @property Carbon $created_at
 * @property EloquentCollection<array-key, Playable> $playables
 * @property EloquentCollection<array-key, User> $users
 * @property EloquentCollection<array-key, User> $collaborators
 * @property ?string $cover The playlist cover's file name
 * @property-read EloquentCollection<array-key, PlaylistFolder> $folders
 * @property int $owner_id
 *
 * @method static PlaylistFactory factory(...$parameters)
 */
#[ObservedBy(PlaylistObserver::class)]
class Playlist extends Model implements AuditableContract, Embeddable
{
    use Auditable;
    use HasFactory;
    use HasUuids;
    use ManagesCollaborators;
    use ManagesPlayables;
    use MorphsToEmbeds;
    use Searchable;

    protected $hidden = ['created_at', 'updated_at'];
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rules' => SmartPlaylistRulesCast::class,
        ];
    }

    protected $appends = ['is_smart'];
    protected $with = ['users', 'collaborators', 'folders'];

    public function playables(): BelongsToMany
    {
        return $this
            ->belongsToMany(Playable::class)
            ->withTimestamps()
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role', 'position')->withTimestamps();
    }

    protected function owner(): Attribute
    {
        return Attribute::get(fn () => $this->users()->wherePivot('role', 'owner')->sole())->shouldCache();
    }

    public function collaborators(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'collaborator');
    }

    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(PlaylistFolder::class, null, null, 'folder_id');
    }

    public function collaborationTokens(): HasMany
    {
        return $this->hasMany(PlaylistCollaborationToken::class);
    }

    protected function isSmart(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) $this->rule_groups?->isNotEmpty())->shouldCache();
    }

    protected function ruleGroups(): Attribute
    {
        // aliasing the attribute to avoid confusion
        return Attribute::get(fn () => $this->rules);
    }

    public function ownedBy(User $user): bool
    {
        return $this->owner->is($user);
    }

    /** @inheritdoc */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
