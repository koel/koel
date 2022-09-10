<?php

namespace App\Http\Resources;

use App\Models\PlaylistFolder;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistFolderResource extends JsonResource
{
    public function __construct(private PlaylistFolder $folder)
    {
        parent::__construct($folder);
    }

    /** @return array<mixed> */
    public function toArray($request): array
    {
        return [
            'type' => 'playlist_folders',
            'id' => $this->folder->id,
            'name' => $this->folder->name,
            'user_id' => $this->folder->user_id,
            'created_at' => $this->folder->created_at,
        ];
    }
}
