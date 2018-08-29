<?php

namespace App\Repositories;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class AbstractRepository implements RepositoryInterface
{
    /** @var Model */
    protected $model;
    protected $auth;

    abstract public function getModelClass(): string;

    public function __construct(Guard $auth)
    {
        $this->model = app($this->getModelClass());
        $this->auth = $auth;
    }

    public function getOneById($id): ?Model
    {
        return $this->model->find($id);
    }

    public function getByIds(array $ids): Collection
    {
        return $this->model->whereIn($this->model->getKeyName(), $ids)->get();
    }

    public function getAll(): Collection
    {
        return $this->model->all();
    }
}
