<?php

namespace App\Models\Podcast;

use App\Casts\Podcast\EnclosureCast;
use App\Casts\Podcast\EpisodeMetadataCast;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use PhanAn\Poddle\Values\Enclosure;
use PhanAn\Poddle\Values\EpisodeMetadata;
use Ramsey\Uuid\Uuid;

/**
 * @property EpisodeMetadata $metadata
 * @property string $id
 * @property Carbon $pub_date
 * @property string $podcast_id
 * @property Enclosure $enclosure
 * @property string $title
 * @property Podcast $podcast
 * @property string $guid
 */
class Episode extends Model
{
    use Searchable;

    private const ID_PREFIX = 'e-';

    protected $hidden = ['created_at', 'updated_at'];
    protected $guarded = [];
    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'enclosure' => EnclosureCast::class,
        'metadata' => EpisodeMetadataCast::class,
        'pub_date' => 'datetime',
    ];

    protected $with = ['podcast'];

    protected static function booted(): void
    {
        static::creating(static fn (self $episode) => $episode->id = self::ID_PREFIX . Str::uuid()->toString());
    }

    public function podcast(): BelongsTo
    {
        return $this->belongsTo(Podcast::class);
    }

    /** @inheritDoc */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->metadata->description,
        ];
    }

    public static function isValidId(string $value): bool
    {
        return Str::startsWith($value, self::ID_PREFIX) && Uuid::isValid(Str::after($value, self::ID_PREFIX));
    }
}
