<?php

namespace App\Models;

use App\Casts\SmartPlaylistRulesCast;
use App\Traits\CanFilterByUser;
use App\Values\SmartPlaylistRuleGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;

/**
 * @property int $user_id
 * @property Collection|array $songs
 * @property int $id
 * @property Collection|array<SmartPlaylistRuleGroup> $rule_groups
 * @property bool $is_smart
 * @property string $name
 * @property user $user
 *
 * @method static Builder orderBy(string $field, string $order = 'asc')
 */
class Playlist extends Model
{
    use Searchable;
    use CanFilterByUser;
    use HasFactory;

    protected $hidden = ['user_id', 'created_at', 'updated_at'];
    protected $guarded = ['id'];

    protected $casts = [
        'user_id' => 'int',
        'rules' => SmartPlaylistRulesCast::class,
    ];

    protected $appends = ['is_smart'];

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getIsSmartAttribute(): bool
    {
        return $this->rule_groups->isNotEmpty();
    }

    /** @return Collection|array<SmartPlaylistRuleGroup> */
    public function getRuleGroupsAttribute(): Collection
    {
        return $this->rules;
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
