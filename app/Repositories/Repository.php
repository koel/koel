<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** @template T of Model */
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

    /** @return T */
    public function getOne($id): Model
    {
        return $this->model::query()->findOrFail($id);
    }

    /** @return T|null */
    public function findOneBy(array $params): ?Model
    {
        return $this->model::query()->where($params)->first();
    }

    /** @return T|null */
    public function findOne($id): ?Model
    {
        return $this->model::query()->find($id);
    }

    /** @return T */
    public function getOneBy(array $params): Model
    {
        return $this->model::query()->where($params)->firstOrFail();
    }

    /** @return array<array-key, T>|Collection<array-key, T> */
    public function getMany(array $ids, bool $preserveOrder = false): Collection
    {
        $models = $this->model::query()->find($ids);

        return $preserveOrder ? $models->orderByArray($ids) : $models;
    }

    /** @return array<array-key, T>|Collection<array-key, T> */
    public function getAll(): Collection
    {
        return $this->model::all();
    }

    /** @return T|null  */
    public function getFirstWhere(...$params): ?Model
    {
        return $this->model::query()->firstWhere(...$params);
    }
}
