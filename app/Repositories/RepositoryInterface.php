<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface RepositoryInterface
{
    public function getModelClass(): string;

    /**
     * @param int|string $id
     */
    public function getOneById($id): ?Model;

    /**
     * @param int[]|string[] $ids
     */
    public function getByIds(array $ids): Collection;

    public function getAll(): Collection;
}
