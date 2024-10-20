<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @template T of Model
 * @implements RepositoryInterface<T>
 */
abstract class Repository implements RepositoryInterface
{
    /** @var class-string<T> $modelClass */
    public string $modelClass;

    protected Guard $auth;

    /** @param class-string<T> $modelClass */
    public function __construct(?string $modelClass = null)
    {
        $this->modelClass = $modelClass ?: self::guessModelClass();

        // This instantiation may fail during a console command if e.g. APP_KEY is empty,
        // rendering the whole installation failing.
        rescue(fn () => $this->auth = app(Guard::class));
    }

    /** @return class-string<T> */
    private static function guessModelClass(): string
    {
        return preg_replace('/(.+)\\\\Repositories\\\\(.+)Repository$/m', '$1\Models\\\$2', static::class);
    }

    /** @inheritDoc */
    public function getOne($id): Model
    {
        return $this->modelClass::query()->findOrFail($id);
    }

    /** @inheritDoc */
    public function findOne($id): ?Model
    {
        return $this->modelClass::query()->find($id);
    }

    /** @inheritDoc */
    public function getOneBy(array $params): Model
    {
        return $this->modelClass::query()->where($params)->firstOrFail();
    }

    /** @inheritDoc */
    public function findOneBy(array $params): ?Model
    {
        return $this->modelClass::query()->where($params)->first();
    }

    /** @inheritDoc */
    public function getMany(array $ids, bool $preserveOrder = false): Collection
    {
        $models = $this->modelClass::query()->find($ids);

        return $preserveOrder ? $models->orderByArray($ids) : $models;
    }

    /** @inheritDoc */ // @phpcs:ignore
    public function getAll(): EloquentCollection
    {
        return $this->modelClass::all();
    }

    /** @inheritDoc */
    public function findFirstWhere(...$params): ?Model
    {
        return $this->modelClass::query()->firstWhere(...$params);
    }
}
