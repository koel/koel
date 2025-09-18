<?php

namespace App\Http\Resources;

use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'description',
        'folder_id',
        'user_id',
        'is_smart',
        'rules',
        'created_at',
    ];

    public function __construct(private readonly Playlist $playlist)
    {
        parent::__construct($playlist);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        $user = $request->user() ?? $this->playlist->owner;
        $embedding = $request->routeIs('embeds.payload');

        return [
            'type' => 'playlists',
            'id' => $this->playlist->id,
            'name' => $this->playlist->name,
            'description' => $this->playlist->description,
            'folder_id' => $this->unless($embedding, $this->playlist->getFolderId($user)),
            'user_id' => $this->unless($embedding, $this->playlist->owner->public_id), // backwards compatibility
            'owner_id' => $this->unless($embedding, $this->playlist->owner->public_id),
            'is_smart' => $this->unless($embedding, $this->playlist->is_smart),
            'is_collaborative' => $this->unless($embedding, $this->playlist->is_collaborative),
            'rules' => $this->unless($embedding, $this->playlist->rules),
            'cover' => $this->playlist->cover,
            'created_at' => $this->unless($embedding, $this->playlist->created_at),
        ];
    }
}
