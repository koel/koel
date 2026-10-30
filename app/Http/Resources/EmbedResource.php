<?php

namespace App\Http\Resources;

use App\Models\Embed;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmbedResource extends JsonResource
{
    public const array JSON_STRUCTURE = [
        'type',
        'id',
        'embeddable_type',
        'embeddable_id',
        'user_id',
        'embeddable',
    ];

    public const array JSON_PUBLIC_STRUCTURE = [
        'type',
        'id',
        'embeddable_type',
        'embeddable_id',
        'embeddable',
        'playables',
    ];

    public function __construct(
        private readonly Embed $embed,
        private readonly Model $embeddable,
        private readonly ?Collection $playables = null,
    ) {
        parent::__construct($embed);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        $embedding = $request->routeIs('embeds.payload');

        return [
            'type' => 'embeds',
            'id' => $this->embed->id,
            'embeddable_type' => $this->embed->embeddable_type->value,
            'embeddable_id' => $this->embed->embeddable_id,
            'user_id' => $this->unless($embedding, $this->embed->user->public_id),
            'embeddable' => $this->embed->embeddable_type->resourceClass()::make($this->embeddable),
            'playables' => SongResourceCollection::make($this->whenNotNull($this->playables))->for($this->embed->user),
        ];
    }
}
