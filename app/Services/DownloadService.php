<?php

namespace App\Services;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Models\SongZipArchive;
use App\Services\SongStorages\CloudStorageFactory;
use App\Services\SongStorages\SftpStorage;
use App\Values\Downloadable;
use App\Values\Podcast\EpisodePlayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;

class DownloadService
{
    /**
     * @param Collection<Song>|array<array-key, Song> $songs
     */
    public function getDownloadable(Collection $songs): ?Downloadable
    {
        if ($songs->count() === 1) {
            return optional(
                $this->getLocalPathOrDownloadableUrl($songs->first()), // @phpstan-ignore-line
                static fn (string $url) => Downloadable::make($url)
            );
        }

        return Downloadable::make(
            (new SongZipArchive())
            ->addSongs($songs)
            ->finish()
            ->getPath()
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
