<?php

namespace App\Traits;

use Exception;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * With reference to GitHub issue #463.
 * MySQL and PostgreSQL seem to have a limit of 2^16-1 (65535) elements in an IN statement.
 * This trait provides a method as a workaround to this limitation.
 *
 * @method static Builder whereIn($keys, array $values)
 * @method static Builder whereNotIn($keys, array $values)
 * @method static Builder select(string $string)
 */
trait SupportsDeleteWhereIDsNotIn
{
    /**
     * Deletes all records whose IDs are not in an array.
     *
     * @param array<string>|array<int> $ids the array of IDs
     * @param string         $key name of the primary key
     *
     * @throws Exception
     */
    public static function deleteWhereIDsNotIn(array $ids, string $key = 'id'): void
    {
        $maxChunkSize = config('database.default') === 'sqlite-persistent' ? 999 : 65535;

        // If the number of entries is lower than, or equals to maxChunkSize, just go ahead.
        if (count($ids) <= $maxChunkSize) {
            static::whereNotIn($key, $ids)->delete();

            return;
        }

        // Otherwise, we get the actual IDs that should be deleted…
        $allIDs = static::select($key)->get()->pluck($key)->all();
        $whereInIDs = array_diff($allIDs, $ids);

        // …and see if we can delete them instead.
        if (count($whereInIDs) < $maxChunkSize) {
            static::whereIn($key, $whereInIDs)->delete();

            return;
        }

        // If that's not possible (i.e. this array has more than maxChunkSize elements, too)
        // then we'll delete chunk by chunk.
        static::deleteByChunk($ids, $key, $maxChunkSize);
    }

    /**
     * Delete records chunk by chunk.
     *
     * @param array<string>|array<int> $ids The array of record IDs to delete
     * @param string         $key       Name of the primary key
     * @param int            $chunkSize Size of each chunk. Defaults to 2^16-1 (65535)
     *
     * @throws Exception
     */
    public static function deleteByChunk(array $ids, string $key = 'id', int $chunkSize = 65535): void
    {
        /** @var DatabaseManager $db */
        $db = app(DatabaseManager::class);
        $db->beginTransaction();

        try {
            foreach (array_chunk($ids, $chunkSize) as $chunk) {
                static::whereIn($key, $chunk)->delete();
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
        }
    }
}
