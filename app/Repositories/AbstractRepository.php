<?php

namespace App\Repositories;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Throwable;

abstract class AbstractRepository implements RepositoryInterface
{
    /** @var Model */
    protected $model;

    /** @var Guard */
    protected $auth;

    abstract public function getModelClass(): string;

    public function __construct()
    {
        $this->model = app($this->getModelClass());

        // This instantiation may fail during a console command if e.g. APP_KEY is empty,
        // rendering the whole installation failing.
        try {
            $this->auth = app(Guard::class);
        } catch (Throwable $e) {
        }
    }

    public function getOneById($id): ?Model
    {
        return $this->model->find($id);
    }

    /** @return Collection|array<Model> */
    public function getByIds(array $ids): Collection
    {
        return $this->model->whereIn($this->model->getKeyName(), $ids)->get();
    }

    /** @return Collection|array<Model> */
    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function getFirstWhere(...$params): Model
    {
        return $this->model->where(...$params)->first();
    }
}
