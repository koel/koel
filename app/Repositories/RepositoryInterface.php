<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    public function getOneById($id): ?Model;

    /** @return Collection|array<Model> */
    public function getByIds(array $ids): Collection;

    /** @return Collection|array<Model> */
    public function getAll(): Collection;
}
