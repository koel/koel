<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
    public static function deleteWhereValueNotIn(array $values, ?string $field = null): void
    {
        $field ??= (new static())->getKeyName();

        $maxChunkSize = DB::getDriverName() === 'sqlite' ? 999 : 65_535;

        if (count($values) <= $maxChunkSize) {
            static::query()->whereNotIn($field, $values)->delete();

            return;
        }

        $allIds = static::query()->select($field)->get()->pluck($field)->all();
        $deletableIds = array_diff($allIds, $values);

        if (count($deletableIds) < $maxChunkSize) {
            static::query()->whereIn($field, $deletableIds)->delete();

            return;
        }

        static::deleteByChunk($deletableIds, $maxChunkSize, $field);
    }

    public static function deleteByChunk(array $values, int $chunkSize = 65_535, ?string $field = null): void
    {
        $field ??= (new static())->getKeyName();

        DB::transaction(static function () use ($values, $field, $chunkSize): void {
            foreach (array_chunk($values, $chunkSize) as $chunk) {
                static::query()->whereIn($field, $chunk)->delete();
            }
        });
    }
}
