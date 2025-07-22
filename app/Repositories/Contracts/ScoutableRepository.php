<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template T of Model
 */
interface ScoutableRepository
{
    /**
     * Search for models based on keywords using Laravel Scout.
     *
     * @param string $keywords The search keywords.
     * @param int $limit The maximum number of results to return.
     * @param ?User $user Optional user to scope the search.
     *
     * @return Collection<T>|array<array-key, T> A collection of models matching the search criteria.
     */
    public function search(string $keywords, int $limit, ?User $user = null): Collection;
}
