<?php

namespace App\Http\Resources;

use App\Models\Song;
use Illuminate\Http\Resources\Json\JsonResource;

class SongResource extends JsonResource
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
        'created_at',
    ];

    public const PAGINATION_JSON_STRUCTURE = [
        'data' => [
            '*' => self::JSON_STRUCTURE,
        ],
        'links' => [
            'first',
            'last',
            'prev',
            'next',
        ],
        'meta' => [
            'current_page',
            'from',
            'path',
            'per_page',
            'to',
        ],
    ];

    public function __construct(protected Song $song)
    {
        parent::__construct($song);
    }

    /** @return array<mixed> */
    public function toArray($request): array
    {
        return [
            'type' => 'songs',
            'id' => $this->song->id,
            'owner_id' => $this->song->owner_id,
            'title' => $this->song->title,
            'lyrics' => $this->song->lyrics,
            'album_id' => $this->song->album->id,
            'album_name' => $this->song->album->name,
            'artist_id' => $this->song->artist->id,
            'artist_name' => $this->song->artist->name,
            'album_artist_id' => $this->song->album_artist->id,
            'album_artist_name' => $this->song->album_artist->name,
            'album_cover' => $this->song->album->cover,
            'length' => $this->song->length,
            'liked' => (bool) $this->song->liked,
            'play_count' => (int) $this->song->play_count,
            'track' => $this->song->track,
            'disc' => $this->song->disc,
            'genre' => $this->song->genre,
            'year' => $this->song->year,
            'is_public' => $this->song->is_public,
            'created_at' => $this->song->created_at,
        ];
    }
}
