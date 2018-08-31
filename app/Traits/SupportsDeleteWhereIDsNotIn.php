<?php

namespace App\Traits;

use Exception;
use Illuminate\Database\DatabaseManager;

/**
 * With reference to GitHub issue #463.
 * MySQL and PostgreSQL seem to have a limit of 2^16-1 (65535) elements in an IN statement.
 * This trait provides a method as a workaround to this limitation.
 */
trait SupportsDeleteWhereIDsNotIn
{
    /**
     * Deletes all records whose IDs are not in an array.
     *
     * @param string[]|int[] $ids The array of IDs.
     * @param string         $key Name of the primary key.
     *
     * @throws Exception
     */
    public static function deleteWhereIDsNotIn(array $ids, string $key = 'id'): void
    {
        // If the number of entries is lower than, or equals to 65535, just go ahead.
        if (count($ids) <= 65535) {
            static::whereNotIn($key, $ids)->delete();

            return;
        }

        // Otherwise, we get the actual IDs that should be deleted…
        $allIDs = static::select($key)->get()->pluck($key)->all();
        $whereInIDs = array_diff($allIDs, $ids);

        // …and see if we can delete them instead.
        if (count($whereInIDs) < 65535) {
            static::whereIn($key, $whereInIDs)->delete();

            return;
        }

        // If that's not possible (i.e. this array has more than 65535 elements, too)
        // then we'll delete chunk by chunk.
        static::deleteByChunk($ids, $key);
    }

    /**
     * Delete records chunk by chunk.
     *
     * @param string[]|int[] $ids       The array of record IDs to delete
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
        } catch (Exception $e) {
            $db->rollBack();
        }
    }
}
