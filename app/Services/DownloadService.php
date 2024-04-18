<?php

namespace App\Services;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Models\SongZipArchive;
use App\Services\SongStorages\CloudStorage;
use App\Services\SongStorages\DropboxStorage;
use App\Services\SongStorages\S3CompatibleStorage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class DownloadService
{
    public function getDownloadablePath(Collection $songs): ?string
    {
        if ($songs->count() === 1) {
            return $this->getLocalPath($songs->first());
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

        if ($song->storage === SongStorageType::LOCAL) {
            return File::exists($song->path) ? $song->path : null;
        }

        switch ($song->storage) {
            case SongStorageType::DROPBOX:
                /** @var CloudStorage $cloudStorage */
                $cloudStorage = app(DropboxStorage::class);
                break;

            case SongStorageType::S3:
            case SongStorageType::S3_LAMBDA:
                /** @var CloudStorage $cloudStorage */
                $cloudStorage = app(S3CompatibleStorage::class);
                break;

            default:
                return null;
        }

        return $cloudStorage->copyToLocal($song);
    }
}
