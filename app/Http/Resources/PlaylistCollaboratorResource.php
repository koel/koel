<?php

namespace App\Http\Resources;

use App\Values\PlaylistCollaborator;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistCollaboratorResource extends JsonResource
{
    public function __construct(private PlaylistCollaborator $collaborator)
    {
        parent::__construct($collaborator);
    }

    /** @return array<mixed> */
    public function toArray($request): array
    {
        return [
            'type' => 'playlist_collaborators',
            'id' => $this->collaborator->id,
            'name' => $this->collaborator->name,
            'avatar' => $this->collaborator->avatar,
        ];
    }
}
