<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/** @template T of Model */
interface Repository
{
    /**
     * Resolve a model instance from an ID or model.
     *
     * @param T|string|int $modelOrId
     *
     * @return T
     */
    public function resolveOne(Model|string|int $modelOrId): Model;

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

    /** @return Collection<array-key, T> */
    public function getAll(): Collection;

    /** @return T|null */
    public function findFirstWhere(...$params): ?Model;
}
