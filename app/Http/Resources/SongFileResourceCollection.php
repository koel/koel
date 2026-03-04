<?php

namespace App\Http\Resources;

class SongFileResourceCollection extends SongResourceCollection
{
    /** @inheritdoc */
    public function toArray($request): array
    {
        $user = $this->user ?? auth()->user();

        return $this->collection->map(static fn (SongFileResource $resource) => $resource->for($user))->toArray();
    }
}
