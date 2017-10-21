<?php

namespace App\Traits;

use DB;
use Exception;

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
     * @param array  $ids The array of IDs.
     * @param string $key Name of the primary key.
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public static function deleteWhereIDsNotIn(array $ids, $key = 'id')
    {
        // If the number of entries is lower than, or equals to 65535, just go ahead.
        if (count($ids) <= 65535) {
            return static::whereNotIn($key, $ids)->delete();
        }

        // Otherwise, we get the actual IDs that should be deleted…
        $allIDs = static::select($key)->get()->pluck($key)->all();
        $whereInIDs = array_diff($allIDs, $ids);
        // …and see if we can delete them instead.
        if (count($whereInIDs) < 65535) {
            return static::whereIn($key, $whereInIDs)->delete();
        }

        // If that's not possible (i.e. this array has more than 65535 elements, too)
        // then we'll delete chunk by chunk.
        static::deleteByChunk($ids, $key);

        return $whereInIDs;
    }

    /**
     * Delete records chunk by chunk.
     *
     * @param array  $ids       The array of record IDs to delete
     * @param string $key       Name of the primary key
     * @param int    $chunkSize Size of each chunk. Defaults to 2^16-1 (65535)
     *
     * @throws \Exception
     */
    public static function deleteByChunk(array $ids, $key = 'id', $chunkSize = 65535)
    {
        DB::beginTransaction();

        try {
            foreach (array_chunk($ids, $chunkSize) as $chunk) {
                static::whereIn($key, $chunk)->delete();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
        }
    }
}
