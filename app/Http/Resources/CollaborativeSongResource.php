<?php

namespace App\Http\Resources;

use Carbon\Carbon;

class CollaborativeSongResource extends SongResource
{
    /** @return array<mixed> */
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'collaboration' => [
                'user' => [
                    'avatar' => gravatar($this->song->collaborator_email),
                    'name' => $this->song->collaborator_name,
                ],
                'added_at' => $this->song->added_at,
                'fmt_added_at' => $this->song->added_at ? Carbon::make($this->song->added_at)->diffForHumans() : null,
            ],
        ]);
    }
}
