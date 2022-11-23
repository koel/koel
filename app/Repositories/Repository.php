<?php

namespace App\Repositories;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class Repository implements RepositoryInterface
{
    private string $modelClass;
    protected Model $model;
    protected Guard $auth;

    public function __construct(?string $modelClass = null)
    {
        $this->modelClass = $modelClass ?: self::guessModelClass();
        $this->model = app($this->modelClass);

        // This instantiation may fail during a console command if e.g. APP_KEY is empty,
        // rendering the whole installation failing.
        attempt(fn () => $this->auth = app(Guard::class), false);
    }

    public abstract function guessModelClass(): string;

    public function getOneById($id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * @param array $ids
     * @return Collection
     */
    public function getByIds(array $ids): Collection
    {
        return $this->model->find($ids);
    }

    /**
     * @return Collection
     */
    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function getFirstWhere(...$params): ?Model
    {
        return $this->model->firstWhere(...$params);
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }
}
