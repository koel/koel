<?php

namespace App\Http\Resources;

use App\Models\Podcast;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class PodcastResourceCollection extends ResourceCollection
{
    public function __construct(
        private readonly Collection $podcasts,
        private readonly bool $withSubscriptionData = true
    ) {
        parent::__construct($this->podcasts);
    }

    /** @inheritDoc */
    public function toArray(Request $request): array
    {
        return $this->podcasts->map(function (Podcast $podcast): PodcastResource {
            return PodcastResource::make($podcast, $this->withSubscriptionData);
        })->toArray();
    }
}
