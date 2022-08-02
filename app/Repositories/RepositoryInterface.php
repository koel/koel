<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface RepositoryInterface
{
    public function getOneById($id): ?Model;

    /** @return Collection|array<Model> */
    public function getByIds(array $ids): Collection;

    /** @return Collection|array<Model> */
    public function getAll(): Collection;
}
