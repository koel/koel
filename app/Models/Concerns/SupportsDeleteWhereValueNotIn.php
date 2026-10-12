<?php

namespace App\Models\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;

/**
 * With reference to GitHub issue #463.
 * MySQL and PostgresSQL seem to have a limit of 2^16-1 (65535) elements in an IN statement.
 * This trait provides a method as a workaround to this limitation.
 *
 * @method static Builder query()
 */
trait SupportsDeleteWhereValueNotIn
{
    /**
     * Deletes all records whose certain value is not in an array.
     */
    public static function deleteWhereValueNotIn(
        array $values,
        ?string $field = null,
        ?Closure $queryModifier = null,
    ): int {
        $field ??= (new static())->getKeyName();
        $queryModifier ??= static fn (Builder $builder) => $builder;

        $maxChunkSize = DB::getDriverName() === 'sqlite' ? 999 : 65_535;

        if (count($values) <= $maxChunkSize) {
            return self::deleteAndUnsearch($queryModifier(static::query())->whereNotIn($field, $values));
        }

        $allIds = $queryModifier(static::query())->select($field)->get()->pluck($field)->all();
        $deletableIds = array_diff($allIds, $values);

        if (count($deletableIds) < $maxChunkSize) {
            return self::deleteAndUnsearch($queryModifier(static::query())->whereIn($field, $deletableIds));
        }

        return static::deleteByChunk($deletableIds, $maxChunkSize, $field);
    }

    public static function deleteByChunk(array $values, int $chunkSize = 65_535, ?string $field = null): int
    {
        $field ??= (new static())->getKeyName();

        return DB::transaction(static function () use ($values, $field, $chunkSize): int {
            $deleted = 0;

            foreach (array_chunk($values, $chunkSize) as $chunk) {
                $deleted += self::deleteAndUnsearch(static::query()->whereIn($field, $chunk));
            }

            return $deleted;
        });
    }

    private static function deleteAndUnsearch(Builder $query): int
    {
        if (in_array(Searchable::class, class_uses_recursive(static::class), true)) {
            $query->unsearchable(); // @phpstan-ignore-line
        }

        return $query->delete();
    }
}
