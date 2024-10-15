<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** @template T of Model */
interface RepositoryInterface
{
    /** @return T */
    public function getOne($id): Model;

    /** @return T */
    public function getOneBy(array $params): Model;

    /** @return T|null */
    public function findOne($id): ?Model;

    /** @return T|null */
    public function findOneBy(array $params): ?Model;

    /** @return Collection<array-key, T> */
    public function getMany(array $ids, bool $preserveOrder = false): Collection;

    /** @return Collection<int, T> */
    public function getAll(): EloquentCollection;

    /** @return T|null */
    public function findFirstWhere(...$params): ?Model;
}
