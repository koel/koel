<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface RepositoryInterface
{
    public function getOne($id): Model;

    public function findOne($id): ?Model;

    /** @return Collection<Model> */
    public function getMany(array $ids, bool $preserveOrder = false): Collection;

    /** @return Collection<Model> */
    public function getAll(): Collection;
}
