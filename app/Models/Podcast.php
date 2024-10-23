<?php

namespace App\Models;

use App\Builders\PodcastBuilder;
use App\Casts\Podcast\CategoriesCast;
use App\Casts\Podcast\PodcastMetadataCast;
use App\Models\Song as Episode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use PhanAn\Poddle\Values\CategoryCollection;
use PhanAn\Poddle\Values\ChannelMetadata;

/**
 * @property string $id
 * @property string $url
 * @property string $title
 * @property string $description
 * @property CategoryCollection $categories
 * @property ChannelMetadata $metadata
 * @property string $image
 * @property string $link
 * @property Collection<User> $subscribers
 * @property Collection<Episode> $episodes
 * @property int $added_by
 * @property Carbon $last_synced_at
 * @property ?string $author
 */
class Podcast extends Model
{
    use HasFactory;
    use HasUuids;
    use Searchable;

    protected $hidden = ['created_at', 'updated_at'];
    protected $guarded = [];
    protected $with = ['subscribers'];

    protected $casts = [
        'categories' => CategoriesCast::class,
        'metadata' => PodcastMetadataCast::class,
        'last_synced_at' => 'datetime',
        'explicit' => 'boolean',
    ];

    public static function query(): PodcastBuilder
    {
        return parent::query();
    }

    public function newEloquentBuilder($query): PodcastBuilder
    {
        return new PodcastBuilder($query);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderByDesc('created_at');
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(PodcastUserPivot::class)
            ->withPivot('state')
            ->withTimestamps();
    }

    /** @return array<mixed> */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'title' => $this->title,
            'description' => $this->description,
            'author' => $this->metadata->author,
        ];
    }
}
