<?php

namespace App\Services;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Models\SongZipArchive;
use App\Services\SongStorages\DropboxStorage;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Services\SongStorages\SftpStorage;
use App\Values\Podcast\EpisodePlayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;

class DownloadService
{
    public function getDownloadablePath(Collection $songs): ?string
    {
        if ($songs->count() === 1) {
            return $this->getLocalPath($songs->first()); // @phpstan-ignore-line
        }

        return (new SongZipArchive())
            ->addSongs($songs)
            ->finish()
            ->getPath();
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

        switch ($song->storage) {
            case SongStorageType::DROPBOX:
                $cloudStorage = app(DropboxStorage::class);
                break;

            case SongStorageType::S3:
            case SongStorageType::S3_LAMBDA:
                $cloudStorage = app(S3CompatibleStorage::class);
                break;

            default:
                return null;
        }

        return $cloudStorage->copyToLocal($song);
    }
}
