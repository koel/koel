<?php

namespace App\Http\Resources;

class CollaborativeSongResourceCollection extends SongResourceCollection
{
    /** @inheritdoc */
    public function toArray($request): array
    {
        $user = $this->user ?? auth()->user();

        return $this->collection->map(
            static fn (CollaborativeSongResource $resource) => $resource->for($user)
        )->toArray();
    }
}
