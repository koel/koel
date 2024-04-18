<?php

namespace App\Http\Resources;

use App\Values\ExcerptSearchResult;
use Illuminate\Http\Resources\Json\JsonResource;

class ExcerptSearchResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'songs' => [
            SongResource::JSON_STRUCTURE,
        ],
        'artists' => [
            ArtistResource::JSON_STRUCTURE,
        ],
        'albums' => [
            AlbumResource::JSON_STRUCTURE,
        ],
    ];

    public function __construct(private readonly ExcerptSearchResult $result)
    {
        parent::__construct($result);
    }

    /** @return array<mixed> */
    public function toArray($request): array
    {
        return [
            'songs' => SongResource::collection($this->result->songs),
            'artists' => ArtistResource::collection($this->result->artists),
            'albums' => AlbumResource::collection($this->result->albums),
        ];
    }
}
