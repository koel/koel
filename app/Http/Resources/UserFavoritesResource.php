<?php

namespace App\Http\Resources;

use App\Models\UserFavorites;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserFavoritesResource extends JsonResource
{
    public const array JSON_STRUCTURE = [
        'type',
        'id',
        'owner_name',
    ];

    public function __construct(
        private readonly UserFavorites $favorites,
    ) {
        parent::__construct($favorites);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'favorites',
            'id' => $this->favorites->public_id,
            'owner_name' => $this->favorites->name,
        ];
    }
}
