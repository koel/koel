<?php

namespace App\Repositories;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Throwable;

abstract class AbstractRepository implements RepositoryInterface
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
        try {
            $this->auth = app(Guard::class);
        } catch (Throwable $e) {
        }
    }

    private static function guessModelClass(): string
    {
        return preg_replace('/(.+)\\\\Repositories\\\\(.+)Repository$/m', '$1\Models\\\$2', static::class);
    }

    public function getOneById($id): ?Model
    {
        return $this->model->find($id);
    }

    /** @return Collection|array<Model> */
    public function getByIds(array $ids): Collection
    {
        return $this->model->find($ids);
    }

    /** @return Collection|array<Model> */
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
