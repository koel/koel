<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
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

    private static function guessModelClass(): string
    {
        return preg_replace('/(.+)\\\\Repositories\\\\(.+)Repository$/m', '$1\Models\\\$2', static::class);
    }

    public function getOne($id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function findOne($id): ?Model
    {
        return $this->model->find($id);
    }

    /** @return Collection<array-key, Model> */
    public function getMany(array $ids, bool $inThatOrder = false): Collection
    {
        $models = $this->model::query()->find($ids);

        return $inThatOrder ? $models->orderByArray($ids) : $models;
    }

    /** @return Collection<array-key, Model> */
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
