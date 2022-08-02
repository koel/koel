<?php

namespace App\Http\Resources;

use App\Values\ExcerptSearchResult;
use Illuminate\Http\Resources\Json\JsonResource;

class ExcerptSearchResource extends JsonResource
{
    public function __construct(private ExcerptSearchResult $result)
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
