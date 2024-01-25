<?php

namespace App\Models;

use App\Casts\SmartPlaylistRulesCast;
use App\Facades\License as LicenseFacade;
use App\Values\SmartPlaylistRuleGroupCollection;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/**
 * @property string $id
 * @property string $name
 * @property bool $is_smart
 * @property int $user_id
 * @property User $user
 * @property ?string $folder_id
 * @property ?PlaylistFolder $folder
 * @property Collection|array<array-key, Song> $songs
 * @property ?SmartPlaylistRuleGroupCollection $rule_groups
 * @property ?SmartPlaylistRuleGroupCollection $rules
 * @property Carbon $created_at
 * @property bool $own_songs_only
 * @property Collection|array<array-key, User> $collaborators
 * @property-read bool $is_collaborative
 */
class Playlist extends Model
{
    use Searchable;
    use HasFactory;

    protected $hidden = ['user_id', 'created_at', 'updated_at'];
    protected $guarded = [];

    protected $casts = [
        'rules' => SmartPlaylistRulesCast::class,
        'own_songs_only' => 'bool',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
    protected $appends = ['is_smart'];
    protected $with = ['collaborators'];

    protected static function booted(): void
    {
        static::creating(static function (Playlist $playlist): void {
            $playlist->id ??= Str::uuid()->toString();
        });
    }

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class)->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(PlaylistFolder::class);
    }

    public function collaborationTokens(): HasMany
    {
        return $this->hasMany(PlaylistCollaborationToken::class);
    }

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'playlist_collaborators')->withTimestamps();
    }

    protected function isSmart(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) $this->rule_groups?->isNotEmpty());
    }

    protected function ruleGroups(): Attribute
    {
        // aliasing the attribute to avoid confusion
        return Attribute::get(fn () => $this->rules);
    }

    public function ownedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function inFolder(PlaylistFolder $folder): bool
    {
        return $this->folder_id === $folder->id;
    }

    public function addCollaborator(User $user): void
    {
        if (!$this->hasCollaborator($user)) {
            $this->collaborators()->attach($user);
        }
    }

    public function hasCollaborator(User $user): bool
    {
        return $this->collaborators->contains(static function (User $collaborator) use ($user): bool {
            return $collaborator->is($user);
        });
    }

    /**
     * @param Collection|array<array-key, Song>|Song|array<string> $songs
     */
    public function addSongs(Collection|Song|array $songs, ?User $collaborator = null): void
    {
        $collaborator ??= $this->user;

        if (!is_array($songs)) {
            $songs = Collection::wrap($songs)->pluck('id')->all();
        }

        $this->songs()->attach($songs, ['user_id' =>  $collaborator->id]);
    }

    /**
     * @param Collection|array<array-key, Song>|Song|array<string> $songs
     */
    public function removeSongs(Collection|Song|array $songs): void
    {
        if (!is_array($songs)) {
            $songs = Collection::wrap($songs)->pluck('id')->all();
        }

        $this->songs()->detach($songs);
    }

    protected function isCollaborative(): Attribute
    {
        return Attribute::get(fn (): bool => !$this->is_smart &&
            LicenseFacade::isPlus()
            && $this->collaborators->isNotEmpty());
    }

    /** @return array<mixed> */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
