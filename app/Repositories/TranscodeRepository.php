<?php

namespace App\Repositories;

use App\Models\Transcode;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Repository<Transcode>
 */
class TranscodeRepository extends Repository
{
    /**
     * @param array<string> $songIds
     *
     */
    public function findBySongIds(array $songIds): Collection
    {
        return Transcode::query()->whereIn('song_id', $songIds)->get();
    }
}
