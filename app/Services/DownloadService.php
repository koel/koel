<?php

namespace App\Services;

use App\Models\Song;
use App\Services\Network\SafeHttp;
use App\Services\SongStorages\CloudStorage;
use App\Services\SongStorages\SongStorageFactory;
use App\Values\Downloadable;
use App\Values\Podcast\EpisodePlayable;
use Illuminate\Support\Facades\File;

class DownloadService
{
    public function __construct(
        private readonly SafeHttp $http,
    ) {}

    public function getDownloadable(Song $song): ?Downloadable
    {
        return optional($this->getLocalPathOrDownloadableUrl($song), Downloadable::make(...));
    }

    public function getLocalPathOrDownloadableUrl(Song $song): ?string
    {
        if (!$song->storage->supported()) {
            return null;
        }

        if ($song->isEpisode()) {
            return EpisodePlayable::getForEpisode($song, $this->http)->path;
        }

        $storage = SongStorageFactory::make($song->storage);

        return $storage instanceof CloudStorage
            ? $storage->getPresignedUrl($song->storage_metadata->getPath())
            : $storage->getLocalPath($song->path);
    }

    public function getLocalPath(Song $song): ?string
    {
        if (!$song->storage->supported()) {
            return null;
        }

        if ($song->isEpisode()) {
            return EpisodePlayable::getForEpisode($song, $this->http)->path;
        }

        $localPath = SongStorageFactory::make($song->storage)->getLocalPath($song->path);

        return File::exists($localPath) ? $localPath : null;
    }
}
