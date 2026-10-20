<?php

namespace App\Services;

use App\Models\Song;
use App\Services\Network\SafeHttp;
use App\Services\SongStorages\CloudStorage;
use App\Services\SongStorages\RemoteDiskStorage;
use App\Services\SongStorages\SongStorageFactory;
use App\Values\Downloadable;
use App\Values\Podcast\EpisodePlayable;

class DownloadService
{
    public function __construct(
        private readonly SafeHttp $http,
    ) {}

    public function getDownloadable(Song $song): ?Downloadable
    {
        if (!$song->storage->supported()) {
            return null;
        }

        if ($song->isEpisode()) {
            return Downloadable::make(EpisodePlayable::getForEpisode($song, $this->http)->path);
        }

        $storage = SongStorageFactory::make($song->storage);

        return match (true) {
            $storage instanceof CloudStorage => Downloadable::make(
                $storage->getPresignedUrl($song->storage_metadata->getPath()),
            ),
            $storage instanceof RemoteDiskStorage => Downloadable::make(
                path: $storage->getLocalPath($song->path),
                name: $storage->getOriginalFileName($song->path),
                isTemporaryCopy: true,
            ),
            default => Downloadable::make($storage->getLocalPath($song->path)),
        };
    }
}
