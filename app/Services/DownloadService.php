<?php

namespace App\Services;

use App\Enums\DownloadableType;
use App\Enums\SongStorageType;
use App\Exceptions\DownloadLimitExceededException;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\SongStorages\CloudStorageFactory;
use App\Services\SongStorages\SftpStorage;
use App\Values\Downloadable;
use App\Values\Podcast\EpisodePlayable;
use App\Values\SongZipArchive;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;

class DownloadService
{
    public function __construct(
        private readonly SongRepository $songRepository,
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

    /**
     * @param Collection<Song>|array<array-key, Song> $songs
     */
    public function getDownloadable(Collection $songs): ?Downloadable
    {
        $this->assertWithinLimit($songs->count());

        if ($songs->count() === 1) {
            return optional(
                $this->getLocalPathOrDownloadableUrl($songs->first()), // @phpstan-ignore-line
                Downloadable::make(...),
            );
        }

        return Downloadable::make(
            (new SongZipArchive())
                ->addSongs($songs)
                ->finish()
                ->getPath(),
        );
    }

    private function assertWithinLimit(int $count): void
    {
        throw_if(
            $this->downloadLimit > 0 && $count > $this->downloadLimit,
            new DownloadLimitExceededException($this->downloadLimit),
        );
    }

    // @mago-ignore lint:halstead
    public function getLocalPathOrDownloadableUrl(Song $song): ?string
    {
        if (!$song->storage->supported()) {
            return null;
        }

        if ($song->isEpisode()) {
            // If the song is an episode, get the episode's media URL ("path").
            return $song->path;
        }

        if ($song->storage === SongStorageType::LOCAL) {
            return $song->path;
        }

        if ($song->storage === SongStorageType::SFTP) {
            return app(SftpStorage::class)->copyToLocal($song);
        }

        return CloudStorageFactory::make($song->storage)->getPresignedUrl($song->storage_metadata->getPath());
    }

    public function getLocalPath(Song $song): ?string
    {
        if (!$song->storage->supported()) {
            return null;
        }

        if ($song->isEpisode()) {
            return EpisodePlayable::getForEpisode($song)->path;
        }

        $location = $song->storage_metadata->getPath();

        if ($song->storage === SongStorageType::LOCAL) {
            return File::exists($location) ? $location : null;
        }

        if ($song->storage === SongStorageType::SFTP) {
            /** @var SftpStorage $storage */
            $storage = app(SftpStorage::class);

            return $storage->copyToLocal($location);
        }

        return CloudStorageFactory::make($song->storage)->copyToLocal($location);
    }
}
