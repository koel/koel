<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CollaborativeSongResourceCollection extends ResourceCollection
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
