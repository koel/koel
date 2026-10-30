<?php

namespace App\Services\Embed;

use App\Enums\EmbeddableType;
use App\Exceptions\EmbeddableNotFoundException;
use App\Models\Embed;
use App\Models\Song;
use App\Models\User;
use App\Services\Embed\Resolvers\ResolverRegistry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EmbedService
{
    public function __construct(
        private readonly ResolverRegistry $resolverRegistry,
    ) {}

    public function findEmbeddable(EmbeddableType $type, ?string $embeddableId, User $embedOwner): Model
    {
        $embeddable = $this->resolverRegistry->getResolverForType($type)->findEmbeddable($embeddableId, $embedOwner);

        throw_unless($embeddable, EmbeddableNotFoundException::class);

        return $embeddable;
    }

    public function resolveEmbed(EmbeddableType $type, Model $embeddable, User $user): Embed
    {
        return Embed::query()->firstOrCreate([
            'embeddable_type' => $type,
            'embeddable_id' => $this->resolverRegistry->getResolverForType($type)->getEmbeddableId($embeddable),
            'user_id' => $user->id,
        ]);
    }

    /** @return Collection<int, Song> */
    public function getPlayables(Embed $embed, Model $embeddable): Collection
    {
        return $this->resolverRegistry->getResolverForType($embed->embeddable_type)->getPlayables(
            $embeddable,
            $embed->user,
        );
    }
}
