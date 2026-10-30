<?php

namespace App\Services\Embed\Resolvers\Contracts;

use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TEmbeddable of Model
 */
interface Resolver
{
    /** @return ?TEmbeddable */
    public function findEmbeddable(?string $embeddableId, User $embedOwner): ?Model;

    /**
     * @param TEmbeddable $embeddable
     *
     * @return Collection<int, Song>
     */
    public function getPlayables(Model $embeddable, User $embedOwner): Collection;

    /** @param TEmbeddable $embeddable */
    public function getEmbeddableId(Model $embeddable): ?string;
}
