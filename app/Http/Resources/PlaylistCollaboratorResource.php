<?php

namespace App\Http\Resources;

use App\Values\PlaylistCollaborator;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistCollaboratorResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'avatar',
    ];

    public function __construct(private readonly PlaylistCollaborator $collaborator)
    {
        parent::__construct($collaborator);
    }

    /** @return array<mixed> */
    public function toArray($request): array
    {
        return [
            'type' => 'playlist-collaborators',
            'id' => $this->collaborator->id,
            'name' => $this->collaborator->name,
            'avatar' => $this->collaborator->avatar,
        ];
    }
}
