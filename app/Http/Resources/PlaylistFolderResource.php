<?php

namespace App\Http\Resources;

use App\Models\PlaylistFolder;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistFolderResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'user_id',
        'created_at',
    ];

    public function __construct(private readonly PlaylistFolder $folder)
    {
        parent::__construct($folder);
    }

    /** @inheritdoc */
    public function toArray($request): array
    {
        return [
            'type' => 'playlist-folders',
            'id' => $this->folder->id,
            'name' => $this->folder->name,
            'user_id' => $this->folder->user_id,
            'created_at' => $this->folder->created_at,
        ];
    }
}
