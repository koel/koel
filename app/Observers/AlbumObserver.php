<?php

namespace App\Observers;

use App\Models\Album;
use Illuminate\Log\Logger;
use Throwable;

class AlbumObserver
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function deleted(Album $album): void
    {
        $this->deleteAlbumCover($album);
    }

    private function deleteAlbumCover(Album $album): void
    {
        if (!$album->has_cover) {
            return;
        }

        try {
            unlink($album->cover_path);
        } catch (Throwable $e) {
            $this->logger->error($e);
        }
    }
}
