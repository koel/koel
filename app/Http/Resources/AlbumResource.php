<?php

namespace App\Http\Resources;

use App\Facades\License;
use App\Models\Album;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlbumResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'artist_id',
        'artist_name',
        'cover',
        'created_at',
        'year',
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

    public function __construct(private readonly Album $album)
    {
        parent::__construct($album);
    }

    public function for(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        $isPlus = once(static fn () => License::isPlus());
        $user = $this->user ?? once(static fn () => auth()->user());
        $embedding = $request->routeIs('embeds.payload');

        return [
            'type' => 'albums',
            'id' => $this->album->id,
            'name' => $this->album->name,
            'artist_id' => $this->album->artist->id,
            'artist_name' => $this->album->artist->name,
            'cover' => $this->album->cover,
            'created_at' => $this->unless($embedding, $this->album->created_at),
            'year' => $this->album->year,
            'is_external' => $this->unless(
                $embedding,
                fn () => $isPlus && $this->album->user_id !== $user->id,
            ),
            'favorite' => $this->unless($embedding, $this->album->favorite),
        ];
    }
}
