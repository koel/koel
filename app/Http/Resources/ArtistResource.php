<?php

namespace App\Http\Resources;

use App\Models\Artist;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtistResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'image',
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

    public function __construct(private readonly Artist $artist)
    {
        parent::__construct($artist);
    }

    /** @return array<mixed> */
    public function toArray($request): array
    {
        return [
            'type' => 'artists',
            'id' => $this->artist->id,
            'name' => $this->artist->name,
            'image' => $this->artist->image,
            'created_at' => $this->artist->created_at,
        ];
    }
}
