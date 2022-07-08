<?php

namespace App\Http\Resources;

use App\Models\Artist;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtistResource extends JsonResource
{
    public function __construct(private Artist $artist)
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
            'length' => $this->artist->length,
            'play_count' => (int) $this->artist->play_count,
            'song_count' => (int) $this->artist->song_count,
            'album_count' => (int) $this->artist->album_count,
            'created_at' => $this->artist->created_at,
        ];
    }
}
