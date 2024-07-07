<?php

namespace App\Http\Resources;

use App\Values\PlaylistCollaborator;
use Carbon\Carbon;

class CollaborativeSongResource extends SongResource
{
    public const JSON_STRUCTURE = SongResource::JSON_STRUCTURE + [
        'collaboration' => [
            'user' => PlaylistCollaboratorResource::JSON_STRUCTURE,
            'added_at',
            'fmt_added_at',
        ],
    ];

    /** @return array<mixed> */
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'collaboration' => [
                'user' => PlaylistCollaboratorResource::make(
                    PlaylistCollaborator::make(
                        $this->song->collaborator_id,
                        $this->song->collaborator_name,
                        avatar_or_gravatar($this->song->collaborator_avatar, $this->song->collaborator_email),
                    ),
                ),
                'added_at' => $this->song->added_at,
                'fmt_added_at' => $this->song->added_at ? Carbon::make($this->song->added_at)->diffForHumans() : null,
            ],
        ]);
    }
}
