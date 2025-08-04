<?php

namespace App\Http\Resources;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'favoriteable_type',
        'favoriteable_id',
        'user_id',
        'created_at',
    ];

    public function __construct(private readonly Favorite $favorite)
    {
        parent::__construct($favorite);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'favorites',
            'favoriteable_type' => $this->favorite->favoriteable_type,
            'favoriteable_id' => $this->favorite->favoriteable_id,
            'user_id' => $this->favorite->user_id,
            'created_at' => $this->favorite->created_at,
        ];
    }
}
