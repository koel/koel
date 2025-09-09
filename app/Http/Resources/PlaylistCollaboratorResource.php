<?php

namespace App\Http\Resources;

use App\Values\Playlist\PlaylistCollaborator;
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

    /** @inheritdoc */
    public function toArray($request): array
    {
        return [
            'type' => 'playlist-collaborators',
            'id' => $this->collaborator->publicId,
            'name' => $this->collaborator->name,
            'avatar' => $this->collaborator->avatar,
        ];
    }
}
