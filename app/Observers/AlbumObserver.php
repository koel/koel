<?php

namespace App\Observers;

use App\Models\Album;
use Exception;
use Log;

class AlbumObserver
{
    public function deleted(Album $album): void
    {
        if (!$album->has_cover) {
            return;
        }

        try {
            unlink($album->cover_path);
        } catch (Exception $e) {
            Log::error($e);
        }
    }
}
