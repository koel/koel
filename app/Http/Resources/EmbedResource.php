<?php

namespace App\Http\Resources;

use App\Enums\EmbeddableType;
use App\Models\Embed;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmbedResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'embeddable_type',
        'embeddable_id',
        'user_id',
        'embeddable',
    ];

    public const JSON_PUBLIC_STRUCTURE = [
        'type',
        'id',
        'embeddable_type',
        'embeddable_id',
        'embeddable',
        'playables',
    ];

    public function __construct(
        private readonly Embed $embed,
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
            'embeddable_type' => $this->embed->embeddable_type,
            'embeddable_id' => $this->embed->embeddable_id,
            'user_id' => $this->unless($embedding, $this->embed->user->public_id),
            'embeddable' => $this->transformEmbeddableToResource(),
            'playables' => SongResourceCollection::make($this->whenNotNull($this->playables))->for($this->embed->user),
        ];
    }

    private function transformEmbeddableToResource(): JsonResource
    {
        return EmbeddableType::from($this->embed->embeddable_type)
                ->resourceClass()
                ::make($this->embed->embeddable);
    }
}
