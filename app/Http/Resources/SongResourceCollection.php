<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SongResourceCollection extends ResourceCollection
{
    private ?User $user;

    public function for(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /** @inheritdoc */
    public function toArray($request): array
    {
        $user = $this->user ?? auth()->user();

        return $this->collection->map(static fn (SongResource $resource) => $resource->for($user))->toArray();
    }
}
