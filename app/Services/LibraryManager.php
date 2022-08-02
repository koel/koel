<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class LibraryManager
{
    /**
     * @return array{
     *     albums: Collection<array-key, Album>,
     *     artists: Collection<array-key, Artist>,
     *  }
     */
    public function prune(bool $dryRun = false): array
    {
        return DB::transaction(static function () use ($dryRun): array {
            /** @var Builder $albumQuery */
            $albumQuery = Album::leftJoin('songs', 'songs.album_id', '=', 'albums.id')
                ->whereNull('songs.album_id')
                ->whereNotIn('albums.id', [Album::UNKNOWN_ID]);

            /** @var Builder $artistQuery */
            $artistQuery = Artist::leftJoin('songs', 'songs.artist_id', '=', 'artists.id')
                ->leftJoin('albums', 'albums.artist_id', '=', 'artists.id')
                ->whereNull('songs.artist_id')
                ->whereNull('albums.artist_id')
                ->whereNotIn('artists.id', [Artist::UNKNOWN_ID, Artist::VARIOUS_ID]);

            $results = [
                'albums' => $albumQuery->get('albums.*'),
                'artists' => $artistQuery->get('artists.*'),
            ];

            if (!$dryRun) {
                $albumQuery->delete();
                $artistQuery->delete();
            }

            return $results;
        });
    }
}
