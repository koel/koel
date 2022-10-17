<?php

namespace App\Http\Resources;

use App\Models\Album;
use Illuminate\Http\Resources\Json\JsonResource;

class AlbumResource extends JsonResource
{
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
