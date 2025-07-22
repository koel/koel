<?php

namespace App\Http\Resources;

use App\Enums\SongStorageType;
use App\Models\Song;
use Webmozart\Assert\Assert;

class SongFileResource extends SongResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'title',
        'lyrics',
        'album_id',
        'album_name',
        'artist_id',
        'artist_name',
        'album_artist_id',
        'album_artist_name',
        'album_cover',
        'length',
        'liked',
        'play_count',
        'track',
        'genre',
        'year',
        'disc',
        'is_public',
        'basename',
        'created_at',
    ];

    public function __construct(protected Song $song)
    {
        Assert::true($song->storage === SongStorageType::LOCAL);

        parent::__construct($song);
    }

    /** @inheritdoc */
    public static function collection($resource): SongFileResourceCollection
    {
        return SongFileResourceCollection::make($resource);
    }

    /** @inheritDoc */
    public function toArray($request): array
    {
        $data = parent::toArray($request);
        $data['basename'] = $this->song->basename;

        return $data;
    }
}
