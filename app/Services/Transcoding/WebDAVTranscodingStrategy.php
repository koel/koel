<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Services\SongStorages\WebDAVStorage;
use Illuminate\Support\Facades\File;
use Throwable;
use Webmozart\Assert\Assert;

class WebDAVTranscodingStrategy extends TranscodingStrategy
{
    protected function findOrCreateTranscodeLocation(Song $song, int $bitRate, ?string $localSourcePath): string
    {
        $transcode = $this->findTranscodeBySongAndBitRate($song, $bitRate);

        if ($transcode?->isValid()) {
            return $transcode->location;
        }

        if ($transcode) {
            File::delete($transcode->location);
        }

        /** @var WebDAVStorage $storage */
        $storage = app(WebDAVStorage::class);
        $downloadedSource = $localSourcePath ? null : $storage->copyToLocal($song->storage_metadata->getPath());

        $destination = artifact_path(sprintf('transcodes/%d/%s.m4a', $bitRate, Ulid::generate()));

        try {
            $this->transcodeAndUpsert($song, $localSourcePath ?? $downloadedSource, $destination, $bitRate);
        } catch (Throwable $e) {
            File::delete($destination);

            throw $e;
        } finally {
            if ($downloadedSource) {
                File::delete($downloadedSource);
            }
        }

        return $destination;
    }

    public function deleteTranscodeFile(string $location, SongStorageType $storageType): void
    {
        Assert::eq($storageType, SongStorageType::WEBDAV);

        File::delete($location);
    }
}
