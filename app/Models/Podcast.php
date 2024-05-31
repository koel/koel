<?php

namespace App\Models;

use App\Builders\PodcastBuilder;
use App\Casts\Podcast\CategoriesCast;
use App\Casts\Podcast\PodcastMetadataCast;
use App\Enums\PlayableType;
use App\Models\Song as Episode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use PhanAn\Poddle\Values\CategoryCollection;
use PhanAn\Poddle\Values\ChannelMetadata;
use PhanAn\Poddle\Values\Episode as EpisodeDTO;

/**
 * @property-read string $id
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
    use Searchable;

    protected $hidden = ['created_at', 'updated_at'];
    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'categories' => CategoriesCast::class,
        'metadata' => PodcastMetadataCast::class,
        'last_synced_at' => 'datetime',
        'explicit' => 'boolean',
    ];

    protected $with = ['subscribers'];

    protected static function booted(): void
    {
        static::creating(static fn (self $podcast) => $podcast->id ??= Str::uuid()->toString());
    }

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

    public function addEpisodeByDTO(EpisodeDTO $dto): Episode
    {
        return $this->episodes()->create([
            'title' => $dto->title,
            'path' => $dto->enclosure->url,
            'created_at' => $dto->metadata->pubDate ?: now(),
            'episode_metadata' => $dto->metadata,
            'episode_guid' => $dto->guid,
            'length' => $dto->metadata->duration ?? 0,
            'mtime' => time(),
            'is_public' => true,
            'type' => PlayableType::PODCAST_EPISODE,
        ]);
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
