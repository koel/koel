<?php

namespace App\Http\Resources;

use App\Values\Genre;
use Illuminate\Http\Resources\Json\JsonResource;

class GenreResource extends JsonResource
{
    public function __construct(private Genre $genre)
    {
        parent::__construct($genre);
    }

    /** @return array<mixed> */
    public function toArray($request): array
    {
        return [
            'type' => 'genres',
            'name' => $this->genre->name,
            'song_count' => $this->genre->songCount,
            'length' => $this->genre->length,
        ];
    }
}
