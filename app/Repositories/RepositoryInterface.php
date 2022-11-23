<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface RepositoryInterface
{
    public function getOneById($id): ?Model;

    /**
     * @param array $ids
     * @return Collection
     */
    public function getByIds(array $ids): Collection;

    /**
     * @return Collection
     */
    public function getAll(): Collection;
}
