<?php

namespace App\Http\Resources;

use App\Models\Album;
use Illuminate\Http\Resources\Json\JsonResource;

class AlbumResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'artist_id',
        'artist_name',
        'cover',
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

    public function __construct(private Album $album)
    {
        parent::__construct($album);
    }

    /** @return array<mixed> */
    public function toArray($request): array
    {
        return [
            'type' => 'albums',
            'id' => $this->album->id,
            'name' => $this->album->name,
            'artist_id' => $this->album->artist_id,
            'artist_name' => $this->album->artist->name,
            'cover' => $this->album->cover,
            'created_at' => $this->album->created_at,
        ];
    }
}
