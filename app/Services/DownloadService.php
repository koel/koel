<?php

namespace App\Services;

use App\Enums\DownloadableType;
use App\Exceptions\DownloadLimitExceededException;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\Network\SafeHttp;
use App\Services\SongStorages\CloudStorage;
use App\Services\SongStorages\SongStorageFactory;
use App\Values\Downloadable;
use App\Values\Podcast\EpisodePlayable;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\File;

class DownloadService
{
    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly SafeHttp $http,
        #[Config('koel.download.limit')]
        private readonly int $downloadLimit = 0,
    ) {}

    /**
     * @throws DownloadLimitExceededException
     */
    public function assertWithinDownloadLimit(
        DownloadableType $type,
        User $user,
        array|string|int|null $id = null,
    ): void {
        if ($this->downloadLimit === 0) {
            return;
        }

        $count = match ($type) {
            DownloadableType::Songs => count((array) $id),
            DownloadableType::Album => $this->songRepository->getByAlbum($id, $user)->count(),
            DownloadableType::Artist => $this->songRepository->getByArtist($id, $user)->count(),
            DownloadableType::Playlist => $this->songRepository->getByPlaylist($id, $user)->count(),
            DownloadableType::Favorites => $this->songRepository->getFavorites($user)->count(),
        };

        $this->assertWithinLimit($count);
    }

    public function getDownloadable(Song $song): ?Downloadable
    {
        return optional($this->getLocalPathOrDownloadableUrl($song), Downloadable::make(...));
    }

    private function assertWithinLimit(int $count): void
    {
        throw_if(
            $this->downloadLimit > 0 && $count > $this->downloadLimit,
            new DownloadLimitExceededException($this->downloadLimit),
        );
    }

    public function getLocalPathOrDownloadableUrl(Song $song): ?string
    {
        if (!$song->storage->supported()) {
            return null;
        }

        if ($song->isEpisode()) {
            // If the song is an episode, get the episode's media URL ("path").
            return $song->path;
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
