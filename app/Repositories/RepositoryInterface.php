<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface RepositoryInterface
{
    public function getOne($id): Model;

    public function findOne($id): ?Model;

    /** @return Collection|array<Model> */
    public function getMany(array $ids, bool $inThatOrder = false): Collection;

    /** @return Collection|array<Model> */
    public function getAll(): Collection;
}
