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
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use LogicException;

/**
 * @property string $id
 * @property string $name
 * @property bool $is_smart
 * @property int $user_id
 * @property User $user
 * @property Collection<array-key, Song> $songs
 * @property array<string> $song_ids
 * @property ?SmartPlaylistRuleGroupCollection $rule_groups
 * @property ?SmartPlaylistRuleGroupCollection $rules
 * @property Carbon $created_at
 * @property bool $own_songs_only
 * @property Collection<array-key, User> $collaborators
 * @property-read bool $is_collaborative
 * @property-read ?string $cover The playlist cover's URL
 * @property-read ?string $cover_path
 * @property-read Collection<array-key, PlaylistFolder> $folders
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
    protected $with = ['user', 'collaborators', 'folders'];

    protected static function booted(): void
    {
        static::creating(static function (Playlist $playlist): void {
            $playlist->id ??= Str::uuid()->toString();
        });
    }

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class)
            ->withTimestamps()
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(PlaylistFolder::class, null, null, 'folder_id');
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

    public function songIds(): Attribute
    {
        throw_if($this->is_smart, new LogicException('Smart playlist contents are generated dynamically.'));

        return Attribute::get(fn () => $this->songs->pluck('id')->all());
    }

    protected function cover(): Attribute
    {
        return Attribute::get(static fn (?string $value): ?string => playlist_cover_url($value));
    }

    protected function coverPath(): Attribute
    {
        return Attribute::get(function () {
            $cover = Arr::get($this->attributes, 'cover');

            return $cover ? playlist_cover_path($cover) : null;
        });
    }

    public function ownedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function inFolder(PlaylistFolder $folder): bool
    {
        return $this->folders->contains($folder);
    }

    public function getFolder(?User $contextUser = null): ?PlaylistFolder
    {
        return $this->folders->firstWhere(
            fn (PlaylistFolder $folder) => $folder->user->is($contextUser ?? $this->user)
        );
    }

    public function getFolderId(?User $user = null): ?string
    {
        return $this->getFolder($user)?->id;
    }

    public function addCollaborator(User $user): void
    {
        if (!$this->hasCollaborator($user)) {
            $this->collaborators()->attach($user);
        }
    }

    public function hasCollaborator(User $collaborator): bool
    {
        return $this->collaborators->contains(static fn (User $user): bool => $collaborator->is($user));
    }

    /**
     * @param Collection|array<array-key, Song>|Song|array<string> $songs
     */
    public function addPlayables(Collection|Song|array $songs, ?User $collaborator = null): void
    {
        $collaborator ??= $this->user;
        $maxPosition = $this->songs()->getQuery()->max('position') ?? 0;

        if (!is_array($songs)) {
            $songs = Collection::wrap($songs)->pluck('id')->all();
        }

        $data = [];

        foreach ($songs as $song) {
            $data[$song] = [
                'position' => ++$maxPosition,
                'user_id' => $collaborator->id,
            ];
        }

        $this->songs()->attach($data);
    }

    /**
     * @param Collection<array-key, Song>|Song|array<string> $songs
     */
    public function removePlayables(Collection|Song|array $songs): void
    {
        if (!is_array($songs)) {
            $songs = Collection::wrap($songs)->pluck('id')->all();
        }

        $this->songs()->detach($songs);
    }

    protected function isCollaborative(): Attribute
    {
        return Attribute::get(fn (): bool => !$this->is_smart
            && LicenseFacade::isPlus()
            && $this->collaborators->isNotEmpty());
    }

    /** @inheritdoc */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
