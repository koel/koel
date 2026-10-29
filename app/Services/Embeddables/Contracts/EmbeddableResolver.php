<?php

namespace App\Services\Embeddables\Contracts;

use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @template TEmbeddable of Model
 */
interface EmbeddableResolver
{
    /** @return ?TEmbeddable */
    public function findEmbeddable(?string $embeddableId, User $embedOwner): ?Model;

    /** @param TEmbeddable $embeddable */
    public function canEmbed(User $user, Model $embeddable): bool;

    /**
     * @param TEmbeddable $embeddable
     *
     * @return Collection<int, Song>
     */
    public function getPlayables(Model $embeddable, User $embedOwner): Collection;

    /** @param TEmbeddable $embeddable */
    public function getEmbeddableId(Model $embeddable): ?string;

    /** @param TEmbeddable $embeddable */
    public function makeEmbeddableResource(Model $embeddable): JsonResource;
}
