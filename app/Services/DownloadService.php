<?php

namespace App\Services;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Models\SongZipArchive;
use App\Services\SongStorages\CloudStorage;
use App\Services\SongStorages\DropboxStorage;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Services\SongStorages\SftpStorage;
use App\Values\Downloadable;
use App\Values\Podcast\EpisodePlayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;

class DownloadService
{
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

        return self::resolveCloudStorage($song)?->getSongPresignedUrl($song);
    }

    public function getLocalPath(Song $song): ?string
    {
        if (!$song->storage->supported()) {
            return null;
        }

        if ($song->isEpisode()) {
            return EpisodePlayable::getForEpisode($song)->path;
        }

        if ($song->storage === SongStorageType::LOCAL) {
            return File::exists($song->path) ? $song->path : null;
        }

        if ($song->storage === SongStorageType::SFTP) {
            return app(SftpStorage::class)->copyToLocal($song);
        }

        return self::resolveCloudStorage($song)?->copyToLocal($song);
    }

    private static function resolveCloudStorage(Song $song): ?CloudStorage
    {
        return match ($song->storage) {
            SongStorageType::DROPBOX => app(DropboxStorage::class),
            SongStorageType::S3, SongStorageType::S3_LAMBDA => app(S3CompatibleStorage::class),
            default => null,
        };
    }
}
