<?php

namespace App\Models;

use App\Casts\SmartPlaylistRulesCast;
use App\Values\SmartPlaylistRuleGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
 * @property Collection|array<array-key, SmartPlaylistRuleGroup> $rule_groups
 * @property Collection|array<array-key, SmartPlaylistRuleGroup> $rules
 * @property bool $is_smart
 * @property string $name
 * @property user $user
 *
 * @method static Builder orderBy(string $field, string $order = 'asc')
 */
class Playlist extends Model
{
    use Searchable;
    use HasFactory;

    protected $hidden = ['user_id', 'created_at', 'updated_at'];
    protected $guarded = ['id'];

    protected $casts = [
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

    protected function isSmart(): Attribute
    {
        return Attribute::get(fn (): bool => $this->rule_groups->isNotEmpty());
    }

    /** @return Collection|array<array-key, SmartPlaylistRuleGroup> */
    protected function ruleGroups(): Attribute
    {
        // aliasing the attribute to avoid confusion
        return Attribute::get(fn () => $this->rules);
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
