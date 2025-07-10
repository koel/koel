<?php

namespace App\Http\Resources;

use App\Facades\License;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtistResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'image',
        'created_at',
    ];

    public const PAGINATION_JSON_STRUCTURE = [
        'data' => [
            '*' => self::JSON_STRUCTURE,
        ],
        'links' => [
            'first',
            'last',
            'prev',
            'next',
        ],
        'meta' => [
            'current_page',
            'from',
            'path',
            'per_page',
            'to',
        ],
    ];

    private ?User $user;

    public function __construct(private readonly Artist $artist)
    {
        parent::__construct($artist);
    }

    public function for(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /** @inheritdoc */
    public function toArray($request): array
    {
        $isPlus = once(static fn () => License::isPlus());
        $user = $this->user ?? once(static fn () => auth()->user());

        return [
            'type' => 'artists',
            'id' => $this->artist->public_id,
            'name' => $this->artist->name,
            'image' => $this->artist->image,
            'created_at' => $this->artist->created_at,
            'is_external' => $isPlus && $this->artist->user_id !== $user->id,
        ];
    }
}
