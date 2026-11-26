<?php

namespace App\Observers;

use App\Helpers\SortTitle;
use App\Models\Song;

class SongObserver
{
    public function saving(Song $song): void
    {
        if ($song->isDirty('path')) {
            $song->path_hash = simple_hash($song->path);
        }

        if ($song->isDirty('title') && !$song->isDirty('title_sort') && !self::hasTaggedSortTitle($song)) {
            $song->title_sort = SortTitle::fromTitle($song->title);
        }
    }

    private static function hasTaggedSortTitle(Song $song): bool
    {
        if (!$song->title_sort) {
            return false;
        }

        return $song->title_sort !== SortTitle::fromTitle((string) $song->getOriginal('title'));
    }
}
