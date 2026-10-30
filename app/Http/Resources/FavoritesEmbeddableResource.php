<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoritesEmbeddableResource extends JsonResource
{
    public function __construct(
        private readonly User $owner,
    ) {
        parent::__construct($owner);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'favorites',
            'owner_name' => $this->owner->name,
        ];
    }
}
