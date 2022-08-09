<?php

namespace App\Models;

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
    public static function deleteWhereValueNotIn(array $values, string $field = 'id'): void
    {
        $maxChunkSize = config('database.default') === 'sqlite-persistent' ? 999 : 65535;

        // If the number of entries is lower than, or equals to maxChunkSize, just go ahead.
        if (count($values) <= $maxChunkSize) {
            static::query()->whereNotIn($field, $values)->delete();

            return;
        }

        // Otherwise, we get the actual IDs that should be deleted…
        $allIDs = static::query()->select($field)->get()->pluck($field)->all();
        $whereInIDs = array_diff($allIDs, $values);

        // …and see if we can delete them instead.
        if (count($whereInIDs) < $maxChunkSize) {
            static::query()->whereIn($field, $whereInIDs)->delete();

            return;
        }

        // If that's not possible (i.e. this array has more than maxChunkSize elements, too)
        // then we'll delete chunk by chunk.
        static::deleteByChunk($values, $field, $maxChunkSize);
    }

    public static function deleteByChunk(array $values, string $field = 'id', int $chunkSize = 65535): void
    {
        DB::transaction(static function () use ($values, $field, $chunkSize): void {
            foreach (array_chunk($values, $chunkSize) as $chunk) {
                static::query()->whereIn($field, $chunk)->delete();
            }
        });
    }
}
