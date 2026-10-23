<?php

namespace App\Services;

use App\Enums\FileSizeFillResult;
use App\Models\Song;
use App\Services\SongStorages\SongStorageFactory;

class SongFileSizeService
{
    public function fillFileSize(Song $song): FileSizeFillResult
    {
        if (!$song->storage->supported()) {
            return FileSizeFillResult::SKIPPED;
        }

        $fileSize = SongStorageFactory::make($song->storage)->getFileSize($song->storage_metadata->getPath());

        if ($fileSize === null) {
            return FileSizeFillResult::MISSING;
        }

        $song->updateQuietly(['file_size' => $fileSize]);

        return FileSizeFillResult::FILLED;
    }
}
