<?php

namespace App\Http\Resources;

use App\Models\Playlist;
use App\Services\PlaylistFolderService;
use App\Services\PlaylistService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistResource extends JsonResource
{
    public const array JSON_STRUCTURE = [
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

    public function __construct(
        private readonly Playlist $playlist,
    ) {
        parent::__construct($playlist);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        $user = $request->user() ?? $this->playlist->owner;
        $embedding = $request->routeIs('embeds.payload');

        /** @var PlaylistService $playlistService */
        $playlistService = app(PlaylistService::class);

        /** @var PlaylistFolderService $folderService */
        $folderService = app(PlaylistFolderService::class);

        return [
            'type' => 'playlists',
            'id' => $this->playlist->id,
            'name' => $this->playlist->name,
            'description' => $this->playlist->description,
            'folder_id' => $this->unless($embedding, $folderService->getFolderForPlaylist($this->playlist, $user)?->id),
            'user_id' => $this->unless($embedding, $this->playlist->owner->public_id), // backwards compatibility
            'owner_id' => $this->unless($embedding, $this->playlist->owner->public_id),
            'is_smart' => $this->unless($embedding, $this->playlist->is_smart),
            'is_collaborative' => $this->unless($embedding, $playlistService->isPlaylistCollaborative($this->playlist)),
            'rules' => $this->unless($embedding, $this->playlist->rules),
            'cover' => image_storage_url($this->playlist->cover),
            'created_at' => $this->unless($embedding, $this->playlist->created_at),
        ];
    }
}
