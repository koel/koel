<?php

namespace App\Http\Resources;

use App\Facades\License;
use App\Models\Playlist;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistResource extends JsonResource
{
    public function __construct(private Playlist $playlist)
    {
        parent::__construct($playlist);
    }

    /** @return array<mixed> */
    public function toArray($request): array
    {
        return [
            'type' => 'playlists',
            'id' => $this->playlist->id,
            'name' => $this->playlist->name,
            'folder_id' => $this->playlist->folder_id,
            'user_id' => $this->playlist->user_id,
            'is_smart' => $this->playlist->is_smart,
            'rules' => $this->playlist->rules,
            'collaborators' => License::isPlus() ? UserResource::collection($this->playlist->collaborators) : [],
            'own_songs_only' => $this->playlist->own_songs_only,
            'created_at' => $this->playlist->created_at,
        ];
    }
}
