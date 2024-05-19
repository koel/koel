<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PhanAn\Poddle\Values\Category;

class PodcastCategoryResource extends JsonResource
{
    public function __construct(private readonly Category $category)
    {
        parent::__construct($this->category);
    }

    /** @inheritDoc */
    public function toArray(Request $request): array
    {
        return ['type' => 'podcast-categories'] + $this->category->toArray();
    }
}
